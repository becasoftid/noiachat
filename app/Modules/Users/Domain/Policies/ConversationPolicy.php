<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;

class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator', 'auditor']);
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->viewAny($user);
    }
}
