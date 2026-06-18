<?php

namespace App\Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Billing\Application\Services\PlanLimitService;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Users\Presentation\Requests\StoreUserRequest;
use App\Modules\Users\Presentation\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly PlanLimitService $planLimits,
    ) {}

    public function index(Request $request)
    {
        $this->adoptLegacyUsersInActiveTenant();

        $search = $request->string('search')->toString();

        return view('noia.users.index', [
            'users' => $this->tenantUsersQuery()
                ->with('roles')
                ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                }))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('noia.users.create', [
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $companyId = app(TenantContext::class)->companyId();

        if (! $this->planLimits->canCreate($companyId, 'users', actor: $request->user())) {
            throw ValidationException::withMessages([
                'email' => $this->planLimits->message($companyId, 'users'),
            ]);
        }

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($request->input('roles', []));
        $this->syncTenantMemberships($user, $request->input('roles', []));
        $user->load('roles');

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::CREATE->value,
            'users',
            User::class,
            $user->id,
            null,
            $this->auditSnapshot($user),
            $request,
        );

        return redirect()->route('users.index')->with('status', 'Usuario creado.');
    }

    public function edit(User $user)
    {
        $this->adoptLegacyUserInActiveTenant($user);

        abort_unless($this->tenantUsersQuery()->whereKey($user->id)->exists(), 403);

        return view('noia.users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->adoptLegacyUserInActiveTenant($user);

        abort_unless($this->tenantUsersQuery()->whereKey($user->id)->exists(), 403);

        $oldValues = $this->auditSnapshot($user->load('roles'));
        $roleIds = $request->input('roles', []);

        if ($request->user()->is($user) && ! $request->boolean('is_active')) {
            throw ValidationException::withMessages([
                'is_active' => 'No puedes desactivar tu propio usuario.',
            ]);
        }

        if ($request->user()->is($user) && ! $this->containsAdminRole($roleIds)) {
            throw ValidationException::withMessages([
                'roles' => 'No puedes quitarte tu propio rol administrador.',
            ]);
        }

        $values = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $values['password'] = $request->string('password')->toString();
        }

        $user->update($values);
        $user->roles()->sync($roleIds);
        $this->syncTenantMemberships($user, $roleIds);
        $user->load('roles');

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'users',
            User::class,
            $user->id,
            $oldValues,
            $this->auditSnapshot($user),
            $request,
        );

        return redirect()->route('users.index')->with('status', 'Usuario actualizado.');
    }

    private function containsAdminRole(array $roleIds): bool
    {
        return Role::query()
            ->whereIn('id', $roleIds)
            ->whereIn('name', ['admin', 'company_admin', 'super_admin'])
            ->exists();
    }

    private function auditSnapshot(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles->pluck('name')->values()->all(),
        ];
    }

    private function tenantUsersQuery(): Builder
    {
        $context = app(TenantContext::class);

        return User::query()
            ->whereHas('memberships', function ($query) use ($context): void {
                $query->where('company_id', $context->companyId())
                    ->where('is_active', true);

                if ($context->branchId() !== null) {
                    $query->where(function ($branchQuery) use ($context): void {
                        $branchQuery->where('branch_id', $context->branchId())
                            ->orWhereNull('branch_id');
                    });
                }
            });
    }

    private function syncTenantMemberships(User $user, array $roleIds): void
    {
        $context = app(TenantContext::class);
        $roleIds = collect($roleIds)->map(fn ($id) => (int) $id)->values();

        Membership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $context->companyId())
            ->where('branch_id', $context->branchId())
            ->whereNotIn('role_id', $roleIds)
            ->delete();

        foreach ($roleIds as $index => $roleId) {
            Membership::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'company_id' => $context->companyId(),
                    'branch_id' => $context->branchId(),
                    'role_id' => $roleId,
                ],
                [
                    'is_default' => $index === 0,
                    'is_active' => true,
                ],
            );
        }
    }

    private function adoptLegacyUsersInActiveTenant(): void
    {
        User::query()
            ->whereDoesntHave('memberships')
            ->whereHas('roles')
            ->with('roles')
            ->get()
            ->each(fn (User $user) => $this->syncTenantMemberships($user, $user->roles->pluck('id')->all()));
    }

    private function adoptLegacyUserInActiveTenant(User $user): void
    {
        if ($user->memberships()->exists()) {
            return;
        }

        $roleIds = $user->roles()->pluck('roles.id')->all();

        if ($roleIds !== []) {
            $this->syncTenantMemberships($user, $roleIds);
        }
    }
}
