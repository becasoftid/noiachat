<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Users\Domain\Policies\Concerns\ProtectsTenantAccess;

class MessagePolicy
{
    use ProtectsTenantAccess;

    public function viewAny(User $user): bool
    {
        return $user->canViewActiveTenantOperations();
    }

    public function view(User $user, Message $message): bool
    {
        return $this->viewAny($user) && $this->belongsToActiveTenant($user, $message);
    }

    public function create(User $user): bool
    {
        return $user->canSendActiveTenantMessages();
    }
}
