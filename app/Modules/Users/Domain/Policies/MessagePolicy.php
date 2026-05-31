<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator', 'auditor']);
    }

    public function view(User $user, Message $message): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }
}
