<?php

namespace App\Modules\Billing\Application\Services;

use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleService
{
    /**
     * @return Collection<int, CompanySubscription>
     */
    public function expiredTrialCandidates()
    {
        return CompanySubscription::query()
            ->with(['company', 'plan'])
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->orderBy('trial_ends_at')
            ->get();
    }

    /**
     * @return Collection<int, CompanySubscription>
     */
    public function expireTrials(bool $dryRun = false)
    {
        $subscriptions = $this->expiredTrialCandidates();

        if ($dryRun) {
            return $subscriptions;
        }

        foreach ($subscriptions as $subscription) {
            $oldValues = $this->snapshot($subscription);
            $metadata = $subscription->metadata ?? [];
            $metadata['expired_by'] = 'noiachat:subscriptions-check';
            $metadata['expired_at'] = now()->toISOString();

            $subscription->forceFill([
                'status' => 'expired',
                'current_period_ends_at' => $subscription->current_period_ends_at ?? $subscription->trial_ends_at,
                'metadata' => $metadata,
            ])->save();

            $this->auditExpiration($subscription->fresh(), $oldValues);
        }

        return $subscriptions;
    }

    public function notice(string|null $companyId): ?array
    {
        if ($companyId === null) {
            return null;
        }

        $subscription = CompanySubscription::query()
            ->with('plan')
            ->where('company_id', $companyId)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if ($subscription === null) {
            return [
                'type' => 'danger',
                'message' => 'Esta empresa no tiene una suscripcion activa. Las acciones operativas pueden estar bloqueadas.',
            ];
        }

        if ($subscription->isExpired()) {
            return [
                'type' => 'danger',
                'message' => 'El periodo de prueba vencio. Renueva o cambia de plan para continuar operando.',
            ];
        }

        if ($subscription->status === 'trialing' && $subscription->trial_ends_at !== null) {
            $remainingDays = max(0, now()->diffInDays($subscription->trial_ends_at, false));

            if ($remainingDays <= 3) {
                return [
                    'type' => 'warning',
                    'message' => "Tu prueba vence en {$remainingDays} dias. Revisa el plan antes de que se bloqueen acciones operativas.",
                ];
            }
        }

        return null;
    }

    private function auditExpiration(CompanySubscription $subscription, array $oldValues): void
    {
        DB::table('audit_logs')->insert([
            'company_id' => $subscription->company_id,
            'branch_id' => null,
            'user_id' => null,
            'action' => 'update',
            'module' => 'billing',
            'target_type' => CompanySubscription::class,
            'target_id' => (string) $subscription->id,
            'ip_address' => null,
            'user_agent' => 'console:noiachat:subscriptions-check',
            'old_values_json' => json_encode($oldValues),
            'new_values_json' => json_encode($this->snapshot($subscription)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function snapshot(CompanySubscription $subscription): array
    {
        return [
            'company_id' => $subscription->company_id,
            'plan_id' => $subscription->plan_id,
            'status' => $subscription->status,
            'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
            'current_period_ends_at' => $subscription->current_period_ends_at?->toISOString(),
            'metadata' => $subscription->metadata,
        ];
    }
}
