<?php

namespace App\Modules\Conversations\Application\Services;

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
            'contact_id' => $contactId,
            'channel_id' => $channelId,
            'status' => ConversationStatus::OPEN->value,
            'last_message_at' => now(),
        ]);
    }
}
