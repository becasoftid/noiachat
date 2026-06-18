<?php

namespace App\Modules\Conversations\Application\Services;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Domain\Enums\ConversationStatus;
use App\Modules\Conversations\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;

class ConversationService
{
    public function __construct(private readonly ConversationRepositoryInterface $conversations) {}

    public function findOrCreate(string $contactId, int $channelId): Conversation
    {
        return $this->conversations->findReusable($contactId, $channelId, [
            ConversationStatus::OPEN->value,
            ConversationStatus::PENDING->value,
            ConversationStatus::RESOLVED->value,
        ]) ?? $this->conversations->create([
            ...$this->tenantAttributes($contactId, $channelId),
            'contact_id' => $contactId,
            'channel_id' => $channelId,
            'status' => ConversationStatus::OPEN->value,
            'last_message_at' => now(),
        ]);
    }

    private function tenantAttributes(string $contactId, int $channelId): array
    {
        $channel = Channel::query()->find($channelId);

        if ($channel) {
            return $channel->tenantAttributes();
        }

        return Contact::query()->find($contactId)?->tenantAttributes() ?? [];
    }
}
