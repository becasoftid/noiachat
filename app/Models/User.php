<?php

namespace App\Models;

use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'memberships')
            ->withPivot(['branch_id', 'role_id', 'is_default', 'is_active'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    public function hasActiveTenantRole(array $roles): bool
    {
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $legacyRoles = array_values(array_intersect($roles, ['admin', 'operator', 'auditor']));

        if ($legacyRoles !== [] && $this->hasAnyRole($legacyRoles)) {
            return true;
        }

        if (! app()->bound(TenantContext::class)) {
            return false;
        }

        $membership = app(TenantContext::class)->membership();

        if ($membership === null || $membership->user_id !== $this->id || ! $membership->is_active) {
            return false;
        }

        $roleName = $membership->relationLoaded('role')
            ? $membership->role?->name
            : $membership->role()->value('name');

        return in_array($roleName, $roles, true);
    }

    public function canAdministerActiveTenant(): bool
    {
        return $this->hasActiveTenantRole(['admin', 'company_admin']);
    }

    public function requiresTwoFactorAuthentication(): bool
    {
        return $this->hasAnyRole(config('noiachat.two_factor.admin_roles', [
            'admin',
            'super_admin',
            'company_admin',
            'branch_manager',
        ]));
    }

    public function canManageActiveTenantContacts(): bool
    {
        return $this->hasActiveTenantRole(['admin', 'company_admin', 'branch_manager', 'operator']);
    }

    public function canViewActiveTenantOperations(): bool
    {
        return $this->hasActiveTenantRole(['admin', 'company_admin', 'branch_manager', 'operator', 'auditor']);
    }

    public function canSendActiveTenantMessages(): bool
    {
        return $this->hasActiveTenantRole(['admin', 'company_admin', 'branch_manager', 'operator']);
    }

    public function canViewActiveTenantAudit(): bool
    {
        return $this->hasActiveTenantRole(['admin', 'company_admin', 'branch_manager', 'auditor']);
    }
}
