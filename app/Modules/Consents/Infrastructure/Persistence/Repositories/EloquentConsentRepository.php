<?php

namespace App\Modules\Consents\Infrastructure\Persistence\Repositories;

use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;

class EloquentConsentRepository implements ConsentRepositoryInterface
{
    public function grantedExists(string $contactId, int $channelId): bool
    {
        return ContactConsent::query()
            ->where('contact_id', $contactId)
            ->where('channel_id', $channelId)
            ->where('status', 'granted')
            ->exists();
    }

    public function create(array $attributes): ContactConsent
    {
        return ContactConsent::create($attributes);
    }
}
