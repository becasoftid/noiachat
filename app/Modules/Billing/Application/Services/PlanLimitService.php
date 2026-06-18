<?php

namespace App\Modules\Billing\Application\Services;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Application\Support\TenancyDefaults;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;

class PlanLimitService
{
    public function __construct(
        private readonly SubscriptionFeatureService $subscriptions,
    ) {
    }

    public function canCreate(Company|string|null $company, string $limit, int $quantity = 1, ?User $actor = null): bool
    {
        if ($actor?->hasRole('super_admin')) {
            return true;
        }

        $max = $this->limit($company, $limit);

        if ($max === null) {
            return true;
        }

        return $this->usage($company, $limit) + $quantity <= $max;
    }

    public function limit(Company|string|null $company, string $limit): ?int
    {
        return $this->subscriptions->limit($this->companyId($company), $limit);
    }

    public function usage(Company|string|null $company, string $limit): int
    {
        $companyId = $this->companyId($company);

        if ($companyId === null) {
            return 0;
        }

        return match ($this->normalizeLimit($limit)) {
            'users' => Membership::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->distinct('user_id')
                ->count('user_id'),
            'branches' => Branch::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->count(),
            'contacts' => Contact::query()
                ->where('company_id', $companyId)
                ->count(),
            'whatsapp_channels' => Channel::query()
                ->where('company_id', $companyId)
                ->where('slug', 'whatsapp')
                ->where('is_active', true)
                ->count(),
            default => 0,
        };
    }

    public function message(Company|string|null $company, string $limit): string
    {
        $max = $this->limit($company, $limit);

        return match ($this->normalizeLimit($limit)) {
            'users' => "Tu plan actual permite hasta {$max} usuarios.",
            'branches' => "Tu plan actual permite hasta {$max} sedes.",
            'contacts' => "Tu plan actual permite hasta {$max} contactos.",
            'whatsapp_channels' => "Tu plan actual permite hasta {$max} canales WhatsApp.",
            default => 'Tu plan actual no permite crear mas registros de este tipo.',
        };
    }

    private function normalizeLimit(string $limit): string
    {
        return match ($limit) {
            'max_users' => 'users',
            'max_branches' => 'branches',
            'max_contacts' => 'contacts',
            'max_whatsapp_channels' => 'whatsapp_channels',
            default => $limit,
        };
    }

    private function companyId(Company|string|null $company): ?string
    {
        if ($company instanceof Company) {
            return $company->id;
        }

        if (is_string($company)) {
            return $company;
        }

        if (app()->bound(TenantContext::class) && app(TenantContext::class)->companyId() !== null) {
            return app(TenantContext::class)->companyId();
        }

        return TenancyDefaults::companyId();
    }
}
