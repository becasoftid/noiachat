<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Users\Domain\Policies\Concerns\ProtectsTenantAccess;

class ConversationPolicy
{
    use ProtectsTenantAccess;

    public function viewAny(User $user): bool
    {
        return $user->canViewActiveTenantOperations();
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->viewAny($user) && $this->belongsToActiveTenant($user, $conversation);
    }
}
