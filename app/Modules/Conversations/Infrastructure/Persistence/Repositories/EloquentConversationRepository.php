<?php

namespace App\Modules\Conversations\Infrastructure\Persistence\Repositories;

use App\Modules\Conversations\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentConversationRepository implements ConversationRepositoryInterface
{
    public function findReusable(string $contactId, int $channelId, array $statuses): ?Conversation
    {
        return Conversation::query()
            ->where('contact_id', $contactId)
            ->where('channel_id', $channelId)
            ->whereIn('status', $statuses)
            ->first();
    }

    public function create(array $attributes): Conversation
    {
        return Conversation::create($attributes);
    }

    public function paginateLatest(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return Conversation::query()
            ->with(['contact', 'assignedUser'])
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['assigned_user_id'] ?? null, fn ($q, $value) => $q->where('assigned_user_id', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('last_message_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('last_message_at', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->whereHas('contact', function ($contactQuery) use ($value): void {
                    $contactQuery->where('full_name', 'like', "%{$value}%")
                        ->orWhere('primary_phone', 'like', "%{$value}%");
                });
            })
            ->latest('last_message_at')
            ->paginate($perPage);
    }

    public function loadDetail(Conversation $conversation): Conversation
    {
        $conversation->load([
            'contact',
            'channel',
            'assignedUser',
            'messages.attachments.mediaFile',
            'messages.providerLogs',
            'inboundMessages',
        ]);

        return $conversation;
    }
}
