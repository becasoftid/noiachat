<?php

namespace App\Modules\Consents\Domain\Repositories;

use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;

interface ConsentRepositoryInterface
{
    public function grantedExists(string $contactId, int $channelId): bool;

    public function create(array $attributes): ContactConsent;
}
