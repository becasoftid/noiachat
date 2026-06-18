<?php

namespace App\Modules\Tenancy\Application\Services;

use App\Models\User;
use App\Modules\Tenancy\Application\Support\TenancyDefaults;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;

class DefaultMembershipService
{
    public function ensureFor(User $user): void
    {
        $roleIds = $user->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return;
        }

        $companyId = TenancyDefaults::companyId();
        $branchId = TenancyDefaults::branchId($companyId);

        foreach ($roleIds as $index => $roleId) {
            Membership::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'role_id' => $roleId,
                ],
                [
                    'is_default' => $index === 0,
                    'is_active' => true,
                ],
            );
        }
    }
}
