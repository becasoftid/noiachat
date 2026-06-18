<?php

namespace App\Modules\Users\Domain\Policies\Concerns;

use App\Models\User;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Database\Eloquent\Model;

trait ProtectsTenantAccess
{
    protected function belongsToActiveTenant(User $user, Model $model): bool
    {
        $context = app(TenantContext::class);

        if (! method_exists($model, 'belongsToActiveTenant')) {
            return true;
        }

        return $model->belongsToActiveTenant($context)
            && $this->userHasActiveMembership($user, $context);
    }

    private function userHasActiveMembership(User $user, TenantContext $context): bool
    {
        return $context->membership()?->user_id === $user->id;
    }
}
