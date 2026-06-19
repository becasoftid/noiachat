<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BillingSeeder::class);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'Clinica Aurora',
            'company_legal_name' => 'Clinica Aurora SAS',
            'company_tax_id' => '900123456',
            'branch_name' => 'Sede Principal',
            'branch_city' => 'Bogota',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $company = Company::query()->where('name', 'Clinica Aurora')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('name', 'Sede Principal')->firstOrFail();
        $membership = Membership::query()
            ->where('user_id', $user->id)
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();
        $subscription = CompanySubscription::query()
            ->with('plan')
            ->where('company_id', $company->id)
            ->firstOrFail();

        $this->assertTrue($user->hasRole('company_admin'));
        $this->assertTrue($membership->is_default);
        $this->assertTrue($membership->is_active);
        $this->assertSame('basic_trial', $subscription->plan->code);
        $this->assertSame('trialing', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertSame($membership->id, session('tenant.membership_id'));
    }

    public function test_registration_requires_company_and_branch(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors(['company_name', 'branch_name']);
        $this->assertGuest();
    }

    public function test_registration_validation_messages_are_in_spanish(): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'company_name' => 'Clinica Aurora',
            'branch_name' => 'Sede Principal',
            'password' => 'password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors([
            'password' => 'La confirmacion de contrasena no coincide.',
        ]);
        $this->assertGuest();
    }

    public function test_registration_recovers_when_trial_plan_is_inactive(): void
    {
        Plan::query()->where('code', 'basic_trial')->update(['is_active' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'trial-recovery@example.com',
            'company_name' => 'Clinica Recovery',
            'company_legal_name' => 'Clinica Recovery SAS',
            'company_tax_id' => '901234567',
            'branch_name' => 'Principal',
            'branch_city' => 'Bogota',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('plans', [
            'code' => 'basic_trial',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('companies', [
            'name' => 'Clinica Recovery',
        ]);
    }

    public function test_registered_trial_user_sees_commercial_menu_only(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial@example.com',
            'company_name' => 'Empresa Comercial',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Contactos')
            ->assertSee('Mensajes')
            ->assertSee('Conversaciones')
            ->assertSee('Empresa')
            ->assertSee('Plan')
            ->assertSee('Usuarios')
            ->assertDontSee('Fallos')
            ->assertDontSee('Salud')
            ->assertDontSee('Auditoria')
            ->assertDontSee('Configuracion');
    }

    public function test_registered_trial_user_cannot_access_platform_modules_directly(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-directo@example.com',
            'company_name' => 'Empresa Comercial Directa',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->get(route('settings.index'))->assertForbidden();
        $this->get(route('failures.index'))->assertForbidden();
        $this->get(route('health.index'))->assertForbidden();
        $this->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_registered_trial_user_does_not_see_platform_admin_in_users(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-usuarios@example.com',
            'company_name' => 'Empresa Usuarios',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $companyAdmin = User::query()->where('email', 'comercial-usuarios@example.com')->firstOrFail();
        $company = Company::query()->where('name', 'Empresa Usuarios')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $platformAdmin = User::factory()->create([
            'name' => 'Admin NoiaChat',
            'email' => 'admin@noiachat.local',
        ]);
        $platformAdmin->roles()->attach($adminRole->id);

        Membership::query()->create([
            'user_id' => $platformAdmin->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $adminRole->id,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($companyAdmin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Usuario Comercial')
            ->assertDontSee('Admin NoiaChat')
            ->assertDontSee('admin@noiachat.local');

        $this->actingAs($companyAdmin)
            ->get(route('users.edit', $platformAdmin))
            ->assertForbidden();
    }

    public function test_registered_trial_user_cannot_assign_global_roles(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-roles@example.com',
            'company_name' => 'Empresa Roles',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);

        $this->get(route('users.create'))
            ->assertOk()
            ->assertDontSee('Administrator');

        $this->post(route('users.store'), [
            'name' => 'Usuario Admin Prohibido',
            'email' => 'admin-prohibido@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'is_active' => '1',
            'roles' => [$adminRole->id],
        ])->assertSessionHasErrors('roles');
    }

    public function test_registered_trial_user_does_not_see_platform_admin_in_company_memberships(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-membresias@example.com',
            'company_name' => 'Empresa Membresias',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $companyAdmin = User::query()->where('email', 'comercial-membresias@example.com')->firstOrFail();
        $company = Company::query()->where('name', 'Empresa Membresias')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $platformAdmin = User::factory()->create([
            'name' => 'Admin NoiaChat',
            'email' => 'admin@noiachat.local',
        ]);
        $platformAdmin->roles()->attach($adminRole->id);

        Membership::query()->create([
            'user_id' => $platformAdmin->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $adminRole->id,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($companyAdmin)
            ->get(route('tenancy.index'))
            ->assertOk()
            ->assertSee('Usuario Comercial')
            ->assertDontSee('Admin NoiaChat')
            ->assertDontSee('admin@noiachat.local')
            ->assertDontSee('Administrator');
    }

    public function test_registered_trial_user_cannot_assign_platform_admin_membership(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-asignacion@example.com',
            'company_name' => 'Empresa Asignacion',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $companyAdmin = User::query()->where('email', 'comercial-asignacion@example.com')->firstOrFail();
        $company = Company::query()->where('name', 'Empresa Asignacion')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $adminRole = Role::query()->firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $operatorRole = Role::query()->firstOrCreate(['name' => 'operator'], ['label' => 'Operator']);
        $platformAdmin = User::factory()->create([
            'name' => 'Admin NoiaChat',
            'email' => 'admin-asignacion@noiachat.local',
        ]);
        $platformAdmin->roles()->attach($adminRole->id);

        $this->actingAs($companyAdmin)
            ->post(route('tenancy.memberships.store'), [
                'user_id' => $platformAdmin->id,
                'branch_id' => $branch->id,
                'role_id' => $operatorRole->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('user_id');

        $this->actingAs($companyAdmin)
            ->post(route('tenancy.memberships.store'), [
                'user_id' => $companyAdmin->id,
                'branch_id' => $branch->id,
                'role_id' => $adminRole->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('role_id');
    }
}
