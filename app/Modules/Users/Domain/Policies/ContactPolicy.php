<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator', 'auditor']);
    }

    public function view(User $user, Contact $contact): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->hasAnyRole(['admin', 'operator']);
    }
}
