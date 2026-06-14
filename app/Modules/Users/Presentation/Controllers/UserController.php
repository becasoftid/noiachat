<?php

namespace App\Modules\Users\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Domain\Enums\AuditActionType;
use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Users\Presentation\Requests\StoreUserRequest;
use App\Modules\Users\Presentation\Requests\UpdateUserRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        return view('noia.users.index', [
            'users' => User::query()
                ->with('roles')
                ->when($search !== '', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))
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
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->lower()->toString(),
            'password' => $request->string('password')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($request->input('roles', []));
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
        return view('noia.users.edit', [
            'managedUser' => $user->load('roles'),
            'roles' => Role::query()->orderBy('label')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
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
            ->where('name', 'admin')
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
}
