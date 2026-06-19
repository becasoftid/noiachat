<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\BillingSeeder;
use Illuminate\Support\Str;
use RuntimeException;

class TrialCompanyRegistration
{
    public function createFor(User $user, array $data): Membership
    {
        $plan = $this->trialPlan();
        $role = Role::query()->firstOrCreate(
            ['name' => 'company_admin'],
            ['label' => 'Company admin'],
        );

        $company = Company::query()->create([
            'name' => $data['company_name'],
            'legal_name' => $data['company_legal_name'] ?? null,
            'tax_id' => $data['company_tax_id'] ?? null,
            'slug' => $this->uniqueCompanySlug($data['company_name']),
            'status' => 'active',
            'timezone' => config('app.timezone', 'America/Bogota'),
            'settings' => [],
        ]);

        $branch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => $data['branch_name'],
            'code' => $this->uniqueBranchCode($company->id, $data['branch_name']),
            'city' => $data['branch_city'] ?? null,
            'timezone' => $company->timezone,
            'is_active' => true,
        ]);

        $user->roles()->syncWithoutDetaching([$role->id]);

        CompanySubscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'trial_started_at' => now(),
            'trial_ends_at' => now()->addDays($plan->trial_days),
            'current_period_started_at' => now(),
            'current_period_ends_at' => now()->addDays($plan->trial_days),
            'metadata' => ['source' => 'public_registration'],
        ]);

        return Membership::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function trialPlan(): Plan
    {
        $plan = Plan::query()
            ->where('code', 'basic_trial')
            ->where('is_active', true)
            ->first();

        if ($plan === null) {
            app(BillingSeeder::class)->run();

            $plan = Plan::query()
                ->where('code', 'basic_trial')
                ->where('is_active', true)
                ->first();
        }

        if ($plan === null) {
            throw new RuntimeException('No existe un plan basic_trial activo para registrar nuevas empresas.');
        }

        return $plan;
    }

    private function uniqueCompanySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'empresa';
        $slug = $base;
        $index = 2;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$index}";
            $index++;
        }

        return $slug;
    }

    private function uniqueBranchCode(string $companyId, string $name): string
    {
        $base = Str::slug($name) ?: 'sede';
        $code = $base;
        $index = 2;

        while (Branch::query()->where('company_id', $companyId)->where('code', $code)->exists()) {
            $code = "{$base}-{$index}";
            $index++;
        }

        return $code;
    }
}
