<?php

namespace App\Modules\Users\Domain\Policies;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Users\Domain\Policies\Concerns\ProtectsTenantAccess;

class ContactPolicy
{
    use ProtectsTenantAccess;

    public function viewAny(User $user): bool
    {
        return $user->canViewActiveTenantOperations();
    }

    public function view(User $user, Contact $contact): bool
    {
        return $this->viewAny($user) && $this->belongsToActiveTenant($user, $contact);
    }

    public function create(User $user): bool
    {
        return $user->canManageActiveTenantContacts();
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->canManageActiveTenantContacts() && $this->belongsToActiveTenant($user, $contact);
    }
}
