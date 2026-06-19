<?php

namespace App\Modules\Tenancy\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Billing\Application\Services\PlanLimitService;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TenantAdminController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuditLogger $auditLogger,
        private readonly PlanLimitService $planLimits,
    ) {}

    public function index()
    {
        $company = $this->activeCompany();

        return view('noia.tenancy.index', [
            'company' => $company,
            'companies' => Company::query()
                ->withCount(['branches', 'memberships'])
                ->orderBy('name')
                ->get(),
            'branches' => Branch::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->get(),
            'memberships' => Membership::query()
                ->with(['user', 'branch', 'role'])
                ->where('company_id', $company->id)
                ->when(! auth()->user()?->can('platform.access'), function ($query): void {
                    $query
                        ->whereHas('user', fn ($userQuery) => $this->restrictCommercialUsers($userQuery))
                        ->whereHas('role', fn ($roleQuery) => $this->restrictCommercialRoles($roleQuery));
                })
                ->orderBy(User::select('name')->whereColumn('users.id', 'memberships.user_id'))
                ->paginate(25),
            'users' => User::query()
                ->when(! auth()->user()?->can('platform.access'), fn ($query) => $this->restrictCommercialUsers($query))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'roles' => Role::query()
                ->when(! auth()->user()?->can('platform.access'), fn ($query) => $this->restrictCommercialRoles($query))
                ->orderBy('label')
                ->get(),
        ]);
    }

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            ...$this->globalCompanyRules(),
            'default_branch_name' => ['nullable', 'string', 'max:255'],
            'default_branch_code' => ['nullable', 'string', 'max:255'],
        ]);

        $company = Company::create([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'tax_id' => $validated['tax_id'] ?? null,
            'slug' => $validated['slug'],
            'timezone' => $validated['timezone'],
            'status' => $validated['status'],
        ]);

        if (filled($validated['default_branch_name'] ?? null)) {
            Branch::create([
                'company_id' => $company->id,
                'name' => $validated['default_branch_name'],
                'code' => $validated['default_branch_code'] ?? 'principal',
                'timezone' => $company->timezone,
                'is_active' => true,
            ]);
        }

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::CREATE->value,
            'tenancy',
            Company::class,
            $company->id,
            null,
            $this->companySnapshot($company),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Empresa creada.');
    }

    public function updateGlobalCompany(Request $request, Company $company)
    {
        $oldValues = $this->companySnapshot($company);
        $validated = $request->validate($this->globalCompanyRules($company));

        $company->update($validated);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'tenancy',
            Company::class,
            $company->id,
            $oldValues,
            $this->companySnapshot($company->fresh()),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Empresa global actualizada.');
    }

    public function updateCompany(Request $request)
    {
        $company = $this->activeCompany();
        $oldValues = $this->companySnapshot($company);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $company->update($validated);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'tenancy',
            Company::class,
            $company->id,
            $oldValues,
            $this->companySnapshot($company->fresh()),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Empresa actualizada.');
    }

    public function storeBranch(Request $request)
    {
        $company = $this->activeCompany();

        if (! $this->planLimits->canCreate($company, 'branches', actor: $request->user())) {
            throw ValidationException::withMessages([
                'name' => $this->planLimits->message($company, 'branches'),
            ]);
        }

        $validated = $request->validate($this->branchRules($company));
        $validated['company_id'] = $company->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $branch = Branch::create($validated);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::CREATE->value,
            'tenancy',
            Branch::class,
            $branch->id,
            null,
            $this->branchSnapshot($branch),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Sede creada.');
    }

    public function updateBranch(Request $request, Branch $branch)
    {
        $company = $this->activeCompany();
        $this->ensureBranchBelongsToActiveCompany($branch);
        $oldValues = $this->branchSnapshot($branch);

        $validated = $request->validate($this->branchRules($company, $branch));
        $validated['is_active'] = $request->boolean('is_active');

        $branch->update($validated);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'tenancy',
            Branch::class,
            $branch->id,
            $oldValues,
            $this->branchSnapshot($branch->fresh()),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Sede actualizada.');
    }

    public function storeMembership(Request $request)
    {
        $company = $this->activeCompany();
        $validated = $request->validate($this->membershipRules($company));
        $this->ensureCommercialMembershipIsAssignable((int) $validated['user_id'], (int) $validated['role_id'], $request);
        $branchId = $validated['branch_id'] ?? null;

        $membership = Membership::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'role_id' => $validated['role_id'],
            ],
            [
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active', true),
            ],
        );

        $this->syncDefaultMembership($membership);
        $this->syncUserRolesFromMemberships((int) $validated['user_id']);

        $this->auditLogger->log(
            $request->user()->id,
            $membership->wasRecentlyCreated ? AuditActionType::CREATE->value : AuditActionType::UPDATE->value,
            'tenancy',
            Membership::class,
            $membership->id,
            null,
            $this->membershipSnapshot($membership->fresh(['user', 'branch', 'role'])),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Membresia guardada.');
    }

    public function updateMembership(Request $request, Membership $membership)
    {
        $company = $this->activeCompany();
        $this->ensureMembershipBelongsToActiveCompany($membership);
        $oldValues = $this->membershipSnapshot($membership->load(['user', 'branch', 'role']));
        $oldUserId = $membership->user_id;

        $validated = $request->validate($this->membershipRules($company));
        $this->ensureCommercialMembershipIsAssignable((int) $validated['user_id'], (int) $validated['role_id'], $request);

        $membership->update([
            'user_id' => $validated['user_id'],
            'branch_id' => $validated['branch_id'] ?? null,
            'role_id' => $validated['role_id'],
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->syncDefaultMembership($membership);
        $this->syncUserRolesFromMemberships($oldUserId);
        $this->syncUserRolesFromMemberships((int) $validated['user_id']);

        $this->auditLogger->log(
            $request->user()->id,
            AuditActionType::UPDATE->value,
            'tenancy',
            Membership::class,
            $membership->id,
            $oldValues,
            $this->membershipSnapshot($membership->fresh(['user', 'branch', 'role'])),
            $request,
        );

        return redirect()->route('tenancy.index')->with('status', 'Membresia actualizada.');
    }

    private function activeCompany(): Company
    {
        $company = $this->tenantContext->company();

        abort_unless($company instanceof Company, 403);

        return $company;
    }

    private function branchRules(Company $company, ?Branch $branch = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('branches', 'code')
                    ->where('company_id', $company->id)
                    ->ignore($branch?->id),
            ],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function globalCompanyRules(?Company $company = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('companies', 'slug')->ignore($company?->id)],
            'timezone' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    private function membershipRules(Company $company): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => [
                'nullable',
                'uuid',
                Rule::exists('branches', 'id')->where('company_id', $company->id),
            ],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    private function restrictCommercialUsers($query)
    {
        return $query->whereDoesntHave('roles', function ($roleQuery): void {
            $roleQuery->whereIn('name', ['admin', 'super_admin']);
        });
    }

    private function restrictCommercialRoles($query)
    {
        return $query->whereNotIn('name', ['admin', 'super_admin']);
    }

    private function ensureCommercialMembershipIsAssignable(int $userId, int $roleId, Request $request): void
    {
        if ($request->user()?->can('platform.access')) {
            return;
        }

        $userHasGlobalRole = User::query()
            ->whereKey($userId)
            ->whereHas('roles', function ($query): void {
                $query->whereIn('name', ['admin', 'super_admin']);
            })
            ->exists();

        if ($userHasGlobalRole) {
            throw ValidationException::withMessages([
                'user_id' => 'No puedes asignar administradores globales a esta empresa.',
            ]);
        }

        $roleIsGlobal = Role::query()
            ->whereKey($roleId)
            ->whereIn('name', ['admin', 'super_admin'])
            ->exists();

        if ($roleIsGlobal) {
            throw ValidationException::withMessages([
                'role_id' => 'No puedes asignar roles de administracion global.',
            ]);
        }
    }

    private function ensureBranchBelongsToActiveCompany(Branch $branch): void
    {
        abort_unless($branch->company_id === $this->activeCompany()->id, 403);
    }

    private function ensureMembershipBelongsToActiveCompany(Membership $membership): void
    {
        abort_unless($membership->company_id === $this->activeCompany()->id, 403);
    }

    private function syncDefaultMembership(Membership $membership): void
    {
        if (! $membership->is_default) {
            return;
        }

        Membership::query()
            ->where('user_id', $membership->user_id)
            ->where('company_id', $membership->company_id)
            ->whereKeyNot($membership->id)
            ->update(['is_default' => false]);
    }

    private function syncUserRolesFromMemberships(int $userId): void
    {
        $roleIds = Membership::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('role_id')
            ->unique()
            ->values()
            ->all();

        User::query()->find($userId)?->roles()->sync($roleIds);
    }

    private function companySnapshot(Company $company): array
    {
        return [
            'name' => $company->name,
            'legal_name' => $company->legal_name,
            'tax_id' => $company->tax_id,
            'slug' => $company->slug,
            'timezone' => $company->timezone,
            'status' => $company->status,
        ];
    }

    private function branchSnapshot(Branch $branch): array
    {
        return [
            'company_id' => $branch->company_id,
            'name' => $branch->name,
            'code' => $branch->code,
            'city' => $branch->city,
            'address' => $branch->address,
            'timezone' => $branch->timezone,
            'is_active' => $branch->is_active,
        ];
    }

    private function membershipSnapshot(Membership $membership): array
    {
        return [
            'user_id' => $membership->user_id,
            'user_email' => $membership->user?->email,
            'company_id' => $membership->company_id,
            'branch_id' => $membership->branch_id,
            'branch_name' => $membership->branch?->name,
            'role_id' => $membership->role_id,
            'role_name' => $membership->role?->name,
            'is_default' => $membership->is_default,
            'is_active' => $membership->is_active,
        ];
    }
}
