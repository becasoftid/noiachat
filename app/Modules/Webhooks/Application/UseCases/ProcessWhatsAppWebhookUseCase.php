<?php

namespace App\Modules\Webhooks\Application\UseCases;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Compliance\Application\Services\OptOutKeywordDetector;
use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Conversations\Application\Services\ConversationService;
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
        private readonly ChannelRepositoryInterface $channels,
        private readonly BlacklistRepositoryInterface $blacklist,
    ) {}

    public function execute(array $payload): void
    {
        $this->providerLogs->firstOrCreate(
            ['external_event_id' => data_get($payload, 'entry.0.id') ?: Str::uuid()->toString()],
            ['provider' => 'whatsapp_cloud', 'direction' => 'inbound', 'event_type' => 'webhook', 'payload' => $payload],
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
            $channel = $this->channels->findBySlug('whatsapp');
            $contact = $this->contacts->findByPrimaryPhone($fromPhone);
            if (! $contact && $channel) {
                $contact = $this->contacts->create([
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
                    ['is_primary' => true, 'is_active' => true],
                );
            }

            $conversation = $contact && $channel ? $this->conversations->findOrCreate($contact->id, $channel->id) : null;
            $conversation?->update(['last_message_at' => now()]);

            $inbound = $this->inboundMessages->firstOrCreate(
                ['provider_message_id' => data_get($messagePayload, 'id')],
                ['contact_id' => $contact?->id, 'channel_id' => $channel?->id, 'conversation_id' => $conversation?->id, 'from_phone' => $fromPhone, 'body' => data_get($messagePayload, 'text.body'), 'payload' => $messagePayload],
            );

            if (($contact || $conversation) && (! $inbound->contact_id || ! $inbound->conversation_id)) {
                $inbound->update([
                    'contact_id' => $contact?->id,
                    'channel_id' => $channel?->id,
                    'conversation_id' => $conversation?->id,
                ]);
            }

            if ($keyword = $this->keywordDetector->detect($inbound->body)) {
                if ($contact && $channel) {
                    OptOutRequest::firstOrCreate(['inbound_message_id' => $inbound->id], ['contact_id' => $contact->id, 'channel_id' => $channel->id, 'keyword' => $keyword, 'requested_at' => now()]);
                    $this->blacklist->upsert(['contact_id' => $contact->id, 'channel_id' => $channel->id], ['reason' => 'opt_out', 'created_at' => now()]);
                    $this->auditLogger->log(null, AuditActionType::WEBHOOK_PROCESSED->value, 'webhooks', InboundMessage::class, $inbound->id, null, ['keyword' => $keyword]);
                }
            }
        }
    }
}
