<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Users\Domain\Policies\Concerns\ProtectsTenantAccess;

class AuditLogPolicy
{
    use ProtectsTenantAccess;

    public function viewAny(User $user): bool
    {
        return $user->canViewActiveTenantAudit();
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user) && $this->belongsToActiveTenant($user, $auditLog);
    }
}
