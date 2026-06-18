<?php

namespace App\Modules\Consents\Infrastructure\Persistence\Repositories;

use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactConsent;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;

class EloquentConsentRepository implements ConsentRepositoryInterface
{
    public function grantedExists(string $contactId, int $channelId): bool
    {
        return $this->query()
            ->where('contact_id', $contactId)
            ->where('channel_id', $channelId)
            ->where('status', 'granted')
            ->exists();
    }

    public function create(array $attributes): ContactConsent
    {
        return ContactConsent::create($attributes);
    }

    private function query(): Builder
    {
        $query = ContactConsent::query();
        $context = app(TenantContext::class);

        return $context->companyId() !== null ? $query->forTenantContext($context) : $query;
    }
}
