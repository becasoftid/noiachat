<?php

namespace App\Modules\Billing\Application\Services;

use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;

class SubscriptionOverviewService
{
    public function __construct(
        private readonly PlanLimitService $limits,
        private readonly SubscriptionFeatureService $features,
    ) {}

    public function forCompany(Company|string|null $company): array
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        $subscription = $this->features->subscription($companyId);
        $plan = $subscription?->plan;

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'status_label' => $this->statusLabel($subscription),
            'status_tone' => $this->statusTone($subscription),
            'remaining_trial_days' => $this->features->remainingTrialDays($companyId),
            'limits' => $this->limitRows($companyId),
            'features' => $this->featureRows($subscription),
        ];
    }

    private function limitRows(?string $companyId): array
    {
        return collect([
            ['key' => 'users', 'label' => 'Usuarios'],
            ['key' => 'branches', 'label' => 'Sedes'],
            ['key' => 'contacts', 'label' => 'Contactos'],
            ['key' => 'whatsapp_channels', 'label' => 'Canales WhatsApp'],
        ])->map(function (array $limit) use ($companyId): array {
            $max = $this->limits->limit($companyId, $limit['key']);
            $used = $this->limits->usage($companyId, $limit['key']);

            return [
                ...$limit,
                'used' => $used,
                'max' => $max,
                'available' => $max === null ? null : max(0, $max - $used),
                'percent' => $max === null || $max === 0 ? 0 : min(100, (int) round(($used / $max) * 100)),
            ];
        })->all();
    }

    private function featureRows(?CompanySubscription $subscription): array
    {
        if ($subscription === null || $subscription->plan === null) {
            return [];
        }

        return $subscription->plan->features
            ->sortBy([['module', 'asc'], ['name', 'asc']])
            ->map(fn ($feature): array => [
                'code' => $feature->code,
                'name' => $feature->name,
                'module' => $feature->module,
                'enabled' => (bool) $feature->pivot->enabled,
            ])
            ->values()
            ->all();
    }

    private function statusLabel(?CompanySubscription $subscription): string
    {
        return match ($subscription?->status) {
            'trialing' => $subscription->isExpired() ? 'Prueba vencida' : 'Prueba',
            'active' => 'Activo',
            'past_due' => 'Pago pendiente',
            'expired' => 'Vencido',
            'cancelled' => 'Cancelado',
            default => 'Sin suscripcion',
        };
    }

    private function statusTone(?CompanySubscription $subscription): string
    {
        if ($subscription === null || $subscription->isExpired()) {
            return 'danger';
        }

        return match ($subscription->status) {
            'trialing', 'past_due' => 'warning',
            'active' => 'success',
            default => 'neutral',
        };
    }
}
