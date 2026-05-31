<?php

namespace App\Modules\Consents\Domain\Repositories;

use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;

interface BlacklistRepositoryInterface
{
    public function existsForContactAndChannel(string $contactId, int $channelId): bool;

    public function upsert(array $identity, array $values): ContactBlacklist;
}
