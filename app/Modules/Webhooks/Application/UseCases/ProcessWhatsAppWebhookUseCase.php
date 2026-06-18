<?php

namespace App\Modules\Webhooks\Application\UseCases;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Compliance\Application\Services\OptOutKeywordDetector;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Conversations\Application\Services\ConversationService;
use App\Modules\Messaging\Application\Services\WhatsAppChannelConfig;
use App\Modules\Messaging\Application\Services\MessageStatusService;
use App\Modules\Messaging\Domain\Repositories\InboundMessageRepositoryInterface;
use App\Modules\Messaging\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Messaging\Domain\Repositories\ProviderLogRepositoryInterface;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Shared\Domain\ValueObjects\PhoneNumber;
use App\Modules\Webhooks\Infrastructure\Persistence\Models\OptOutRequest;
use Illuminate\Support\Str;

class ProcessWhatsAppWebhookUseCase
{
    public function __construct(
        private readonly MessageStatusService $statusService,
        private readonly ConversationService $conversations,
        private readonly OptOutKeywordDetector $keywordDetector,
        private readonly AuditLogger $auditLogger,
        private readonly ProviderLogRepositoryInterface $providerLogs,
        private readonly MessageRepositoryInterface $messages,
        private readonly InboundMessageRepositoryInterface $inboundMessages,
        private readonly ContactRepositoryInterface $contacts,
        private readonly BlacklistRepositoryInterface $blacklist,
        private readonly WhatsAppChannelConfig $channelConfig,
    ) {}

    public function execute(array $payload): void
    {
        $channel = $this->channelConfig->channelForWebhookPayload($payload);
        $tenant = $channel?->tenantAttributes() ?? [];

        $this->providerLogs->firstOrCreate(
            ['external_event_id' => data_get($payload, 'entry.0.id') ?: Str::uuid()->toString()],
            [...$tenant, 'provider' => 'whatsapp_cloud', 'direction' => 'inbound', 'event_type' => 'webhook', 'payload' => $payload],
        );

        foreach (data_get($payload, 'entry.0.changes.0.value.statuses', []) as $statusPayload) {
            $message = $this->messages->findByProviderMessageId((string) data_get($statusPayload, 'id'));
            if (! $message) {
                continue;
            }

            $status = match (data_get($statusPayload, 'status')) {
                'sent' => MessageStatus::SENT,
                'delivered' => MessageStatus::DELIVERED,
                'read' => MessageStatus::READ,
                default => MessageStatus::FAILED,
            };

            $this->statusService->transition($message, $status, $statusPayload, 'webhook_status');
        }

        foreach (data_get($payload, 'entry.0.changes.0.value.messages', []) as $messagePayload) {
            $fromPhone = PhoneNumber::from((string) data_get($messagePayload, 'from'))->value();
            if (! $channel) {
                continue;
            }

            $contact = $this->findContactForChannel($fromPhone, $channel);
            if (! $contact && $channel) {
                $contact = $this->contacts->create([
                    ...$channel->tenantAttributes(),
                    'first_name' => 'WhatsApp',
                    'last_name' => null,
                    'full_name' => 'WhatsApp '.$fromPhone,
                    'email' => null,
                    'primary_phone' => $fromPhone,
                    'status' => 'active',
                    'meta' => ['source' => 'whatsapp_webhook'],
                ]);
            }

            if ($contact && $channel) {
                $contact->contactChannels()->firstOrCreate(
                    ['channel_id' => $channel->id, 'phone' => $fromPhone],
                    [...$channel->tenantAttributes(), 'is_primary' => true, 'is_active' => true],
                );
            }

            $conversation = $contact && $channel ? $this->conversations->findOrCreate($contact->id, $channel->id) : null;
            $conversation?->update(['last_message_at' => now()]);

            $inbound = $this->inboundMessages->firstOrCreate(
                ['provider_message_id' => data_get($messagePayload, 'id')],
                [...$tenant, 'contact_id' => $contact?->id, 'channel_id' => $channel?->id, 'conversation_id' => $conversation?->id, 'from_phone' => $fromPhone, 'body' => data_get($messagePayload, 'text.body'), 'payload' => $messagePayload],
            );

            if (($contact || $conversation) && (! $inbound->contact_id || ! $inbound->conversation_id)) {
                $inbound->update([
                    ...$tenant,
                    'contact_id' => $contact?->id,
                    'channel_id' => $channel?->id,
                    'conversation_id' => $conversation?->id,
                ]);
            }

            if ($keyword = $this->keywordDetector->detect($inbound->body)) {
                if ($contact && $channel) {
                    OptOutRequest::firstOrCreate(['inbound_message_id' => $inbound->id], [...$tenant, 'contact_id' => $contact->id, 'channel_id' => $channel->id, 'keyword' => $keyword, 'requested_at' => now()]);
                    $this->blacklist->upsert(['contact_id' => $contact->id, 'channel_id' => $channel->id], [...$tenant, 'reason' => 'opt_out', 'created_at' => now()]);
                    $this->auditLogger->log(null, AuditActionType::WEBHOOK_PROCESSED->value, 'webhooks', InboundMessage::class, $inbound->id, null, ['keyword' => $keyword]);
                }
            }
        }
    }

    private function findContactForChannel(string $phone, Channel $channel): ?Contact
    {
        $normalizedPhone = $this->normalizePhone($phone);

        return Contact::query()
            ->where('company_id', $channel->company_id)
            ->with('contactChannels')
            ->get()
            ->first(function (Contact $contact) use ($normalizedPhone): bool {
                $phones = collect([$contact->primary_phone])
                    ->merge($contact->contactChannels->pluck('phone'))
                    ->map(fn (?string $phone): string => $this->normalizePhone((string) $phone))
                    ->filter();

                return $phones->contains(fn (string $phone): bool => $this->phonesMatch($phone, $normalizedPhone));
            });
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    private function phonesMatch(string $storedPhone, string $incomingPhone): bool
    {
        if ($storedPhone === $incomingPhone) {
            return true;
        }

        return strlen($storedPhone) >= 7
            && strlen($incomingPhone) >= 7
            && (str_ends_with($storedPhone, $incomingPhone) || str_ends_with($incomingPhone, $storedPhone));
    }
}
