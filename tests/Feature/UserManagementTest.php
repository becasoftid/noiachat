<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
    }

    public function test_admin_can_create_user_with_roles(): void
    {
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();

        $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Operador Uno',
            'email' => 'operador@noiachat.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => '1',
            'roles' => [$operatorRole->id],
        ])->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'operador@noiachat.local')->firstOrFail();

        $this->assertTrue($user->hasRole('operator'));
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'create',
            'module' => 'users',
            'target_type' => User::class,
            'target_id' => (string) $user->id,
        ]);
    }

    public function test_admin_can_update_user_roles_and_status(): void
    {
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $auditorRole = Role::query()->where('name', 'auditor')->firstOrFail();
        $user = User::factory()->create(['email' => 'persona@noiachat.local']);
        $user->roles()->attach($operatorRole->id);

        $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => 'Persona Auditora',
            'email' => 'persona.auditora@noiachat.local',
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '0',
            'roles' => [$auditorRole->id],
        ])->assertRedirect(route('users.index'));

        $user->refresh()->load('roles');

        $this->assertSame('Persona Auditora', $user->name);
        $this->assertFalse($user->is_active);
        $this->assertTrue($user->hasRole('auditor'));
        $this->assertFalse($user->hasRole('operator'));
    }

    public function test_operator_cannot_manage_users(): void
    {
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $operator = User::factory()->create();
        $operator->roles()->attach($operatorRole->id);

        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();

        $this->actingAs($this->admin)->put(route('users.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'password' => '',
            'password_confirmation' => '',
            'is_active' => '0',
            'roles' => [$adminRole->id],
        ])->assertSessionHasErrors('is_active');
    }

    public function test_inactive_users_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@noiachat.local',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
