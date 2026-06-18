<?php

namespace App\Console\Commands;

use App\Modules\Billing\Application\Services\SubscriptionLifecycleService;
use Illuminate\Console\Command;

class SubscriptionsCheckCommand extends Command
{
    protected $signature = 'noiachat:subscriptions-check {--dry-run : Lista trials vencidos sin cambiar estado}';

    protected $description = 'Vence suscripciones trialing que ya superaron su fecha de prueba.';

    public function handle(SubscriptionLifecycleService $subscriptions): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $expiredTrials = $subscriptions->expireTrials($dryRun);
        $count = $expiredTrials->count();

        if ($dryRun) {
            $this->line("Trials vencidos detectados: {$count}");
        } else {
            $this->line("Trials vencidos actualizados: {$count}");
        }

        foreach ($expiredTrials as $subscription) {
            $companyName = $subscription->company?->name ?? $subscription->company_id;
            $trialEndsAt = $subscription->trial_ends_at?->toDateTimeString() ?? 'sin fecha';
            $this->line("- {$companyName} ({$subscription->company_id}) vencio {$trialEndsAt}");
        }

        return self::SUCCESS;
    }
}
