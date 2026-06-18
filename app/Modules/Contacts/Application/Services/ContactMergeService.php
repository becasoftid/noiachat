<?php

namespace App\Modules\Contacts\Application\Services;

use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Contacts\Infrastructure\Persistence\Models\ContactChannel;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use App\Modules\Webhooks\Infrastructure\Persistence\Models\OptOutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactMergeService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function merge(Contact $source, Contact $target, int $userId, ?Request $request = null): Contact
    {
        if ($source->is($target)) {
            throw new \InvalidArgumentException('No puedes fusionar un contacto consigo mismo.');
        }

        if ($source->company_id !== $target->company_id) {
            throw new BusinessRuleException('No se pueden fusionar contactos de empresas diferentes.');
        }

        return DB::transaction(function () use ($source, $target, $userId, $request): Contact {
            $sourceSnapshot = $source->fresh(['contactChannels', 'contactConsents', 'contactBlacklist', 'messages', 'inboundMessages', 'conversations'])?->toArray();
            $targetSnapshot = $target->fresh(['contactChannels', 'contactConsents', 'contactBlacklist', 'messages', 'inboundMessages', 'conversations'])?->toArray();

            $this->mergeContactChannels($source, $target);
            $this->mergeConsents($source, $target);
            $this->mergeBlacklist($source, $target);
            $this->mergeConversations($source, $target);

            Message::query()->where('contact_id', $source->id)->update(['contact_id' => $target->id]);
            InboundMessage::query()->where('contact_id', $source->id)->update(['contact_id' => $target->id]);
            OptOutRequest::query()->where('contact_id', $source->id)->update(['contact_id' => $target->id]);

            $source->update([
                'status' => 'invalid',
                'meta' => array_merge($source->meta ?? [], [
                    'merged_into_contact_id' => $target->id,
                    'merged_at' => now()->toISOString(),
                ]),
            ]);
            $source->delete();

            $this->auditLogger->log(
                $userId,
                AuditActionType::UPDATE->value,
                'contacts',
                Contact::class,
                $target->id,
                ['source' => $sourceSnapshot, 'target' => $targetSnapshot],
                ['merged_source_contact_id' => $source->id, 'target_contact_id' => $target->id],
                $request,
            );

            return $target->fresh(['contactChannels', 'contactConsents', 'contactBlacklist', 'messages', 'inboundMessages', 'conversations']);
        });
    }

    private function mergeContactChannels(Contact $source, Contact $target): void
    {
        ContactChannel::query()->where('contact_id', $source->id)->get()->each(function (ContactChannel $channel) use ($target): void {
            $exists = ContactChannel::query()
                ->where('company_id', $target->company_id)
                ->where('channel_id', $channel->channel_id)
                ->where('phone', $channel->phone)
                ->where('is_active', true)
                ->whereKeyNot($channel->id)
                ->exists();

            $exists ? $channel->delete() : $channel->update(['contact_id' => $target->id]);
        });
    }

    private function mergeConsents(Contact $source, Contact $target): void
    {
        ContactConsent::query()->where('contact_id', $source->id)->update(['contact_id' => $target->id]);
    }

    private function mergeBlacklist(Contact $source, Contact $target): void
    {
        ContactBlacklist::query()->where('contact_id', $source->id)->get()->each(function (ContactBlacklist $entry) use ($target): void {
            $exists = ContactBlacklist::query()
                ->where('contact_id', $target->id)
                ->where('channel_id', $entry->channel_id)
                ->exists();

            $exists ? $entry->delete() : $entry->update(['contact_id' => $target->id]);
        });
    }

    private function mergeConversations(Contact $source, Contact $target): void
    {
        Conversation::query()->where('contact_id', $source->id)->get()->each(function (Conversation $sourceConversation) use ($target): void {
            $targetConversation = Conversation::query()
                ->where('contact_id', $target->id)
                ->where('channel_id', $sourceConversation->channel_id)
                ->whereIn('status', ['open', 'pending'])
                ->first();

            if (! $targetConversation) {
                $sourceConversation->update(['contact_id' => $target->id]);

                return;
            }

            Message::query()->where('conversation_id', $sourceConversation->id)->update([
                'contact_id' => $target->id,
                'conversation_id' => $targetConversation->id,
            ]);
            InboundMessage::query()->where('conversation_id', $sourceConversation->id)->update([
                'contact_id' => $target->id,
                'conversation_id' => $targetConversation->id,
            ]);

            $targetConversation->update([
                'last_message_at' => collect([$targetConversation->last_message_at, $sourceConversation->last_message_at])->filter()->max(),
            ]);

            $sourceConversation->delete();
        });
    }
}
