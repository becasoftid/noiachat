<?php

namespace App\Modules\Billing\Application\Services;

use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;

class SubscriptionFeatureService
{
    public function subscription(Company|string|null $company): ?CompanySubscription
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        if ($companyId === null) {
            return null;
        }

        return CompanySubscription::query()
            ->with(['plan.features' => fn ($query) => $query->where('features.is_active', true)])
            ->where('company_id', $companyId)
            ->latest('created_at')
            ->latest('id')
            ->first();
    }

    public function operational(Company|string|null $company): bool
    {
        return $this->subscription($company)?->isOperational() ?? false;
    }

    public function allows(Company|string|null $company, string $featureCode): bool
    {
        $subscription = $this->subscription($company);

        if ($subscription === null || ! $subscription->isOperational()) {
            return false;
        }

        if (! $subscription->plan?->is_active) {
            return false;
        }

        return $subscription->plan->features
            ->contains(fn ($feature) => $feature->code === $featureCode && (bool) $feature->pivot->enabled);
    }

    public function limit(Company|string|null $company, string $limit): ?int
    {
        $subscription = $this->subscription($company);

        if ($subscription === null || $subscription->plan === null) {
            return null;
        }

        $attribute = match ($limit) {
            'users', 'max_users' => 'max_users',
            'branches', 'max_branches' => 'max_branches',
            'contacts', 'max_contacts' => 'max_contacts',
            'whatsapp_channels', 'max_whatsapp_channels' => 'max_whatsapp_channels',
            default => null,
        };

        if ($attribute === null) {
            return null;
        }

        return $subscription->plan->{$attribute};
    }

    public function remainingTrialDays(Company|string|null $company): ?int
    {
        $subscription = $this->subscription($company);

        if ($subscription === null || $subscription->status !== 'trialing' || $subscription->trial_ends_at === null) {
            return null;
        }

        return max(0, now()->diffInDays($subscription->trial_ends_at, false));
    }
}
