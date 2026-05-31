<?php

namespace App\Modules\Messaging\Domain\Repositories;

use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MessageRepositoryInterface
{
    public function create(array $attributes): Message;

    public function paginateLatest(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function findByProviderMessageId(string $providerMessageId): ?Message;

    public function findById(string $id): ?Message;

    public function loadDetail(Message $message): Message;

    public function retryableStatuses(): array;
}
