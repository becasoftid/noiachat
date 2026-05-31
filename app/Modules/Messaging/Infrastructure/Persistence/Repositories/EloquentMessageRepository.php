<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Repositories;

use App\Modules\Messaging\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function create(array $attributes): Message
    {
        return Message::create($attributes);
    }

    public function paginateLatest(int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        return Message::query()
            ->with(['contact', 'events'])
            ->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['type'] ?? null, fn ($q, $value) => $q->where('type', $value))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->when($filters['search'] ?? null, function ($query, $value): void {
                $query->whereHas('contact', function ($contactQuery) use ($value): void {
                    $contactQuery->where('full_name', 'like', "%{$value}%")
                        ->orWhere('primary_phone', 'like', "%{$value}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findByProviderMessageId(string $providerMessageId): ?Message
    {
        return Message::query()->where('provider_message_id', $providerMessageId)->first();
    }

    public function findById(string $id): ?Message
    {
        return Message::query()->find($id);
    }

    public function loadDetail(Message $message): Message
    {
        $message->load(['contact', 'events', 'attachments.mediaFile', 'providerLogs']);

        return $message;
    }

    public function retryableStatuses(): array
    {
        return [
            MessageStatus::FAILED->value,
            MessageStatus::BOUNCED->value,
            MessageStatus::BLOCKED_BY_POLICY->value,
        ];
    }
}
