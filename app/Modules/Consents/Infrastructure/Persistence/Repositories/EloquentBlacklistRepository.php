<?php

namespace App\Modules\Consents\Infrastructure\Persistence\Repositories;

use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;

class EloquentBlacklistRepository implements BlacklistRepositoryInterface
{
    public function existsForContactAndChannel(string $contactId, int $channelId): bool
    {
        return ContactBlacklist::query()
            ->where('contact_id', $contactId)
            ->where(function ($query) use ($channelId): void {
                $query->whereNull('channel_id')->orWhere('channel_id', $channelId);
            })
            ->exists();
    }

    public function upsert(array $identity, array $values): ContactBlacklist
    {
        return ContactBlacklist::updateOrCreate($identity, $values);
    }
}
