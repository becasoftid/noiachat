<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'auditor']);
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $this->viewAny($user);
    }
}
