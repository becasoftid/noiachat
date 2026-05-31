<?php

namespace App\Modules\Conversations\Domain\Repositories;

use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ConversationRepositoryInterface
{
    public function findReusable(string $contactId, int $channelId, array $statuses): ?Conversation;

    public function create(array $attributes): Conversation;

    public function paginateLatest(int $perPage = 20, array $filters = []): LengthAwarePaginator;

    public function loadDetail(Conversation $conversation): Conversation;
}
