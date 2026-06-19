<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\BillingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            ->assertSee('href="'.route('whatsapp.channels.index').'"', false)
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

    public function test_company_admin_has_commercial_whatsapp_permission_without_platform_access(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp@example.com',
            'company_name' => 'Empresa WhatsApp',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $companyAdmin = User::query()->where('email', 'comercial-whatsapp@example.com')->firstOrFail();
        app(TenantContext::class)->setMembership($companyAdmin->memberships()->with('role')->firstOrFail());

        $this->assertTrue($companyAdmin->can('admin.access'));
        $this->assertTrue($companyAdmin->can('whatsapp.integration.manage'));
        $this->assertFalse($companyAdmin->can('platform.access'));
    }

    public function test_operator_cannot_manage_commercial_whatsapp_integration(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-operador-whatsapp@example.com',
            'company_name' => 'Empresa Operador WhatsApp',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa Operador WhatsApp')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $operatorRole = Role::query()->firstOrCreate(['name' => 'operator'], ['label' => 'Operator']);
        $operator = User::factory()->create([
            'name' => 'Operador WhatsApp',
            'email' => 'operador-whatsapp@example.com',
        ]);
        $operator->roles()->attach($operatorRole->id);
        $membership = Membership::query()->create([
            'user_id' => $operator->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $operatorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        app(TenantContext::class)->setMembership($membership->load('role'));

        $this->assertFalse($operator->can('admin.access'));
        $this->assertFalse($operator->can('whatsapp.integration.manage'));
        $this->assertFalse($operator->can('platform.access'));

        $this->actingAs($operator)
            ->get(route('whatsapp.channels.index'))
            ->assertForbidden();
    }

    public function test_company_admin_can_open_commercial_whatsapp_channels_screen(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-vista@example.com',
            'company_name' => 'Empresa WhatsApp Vista',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->get(route('whatsapp.channels.index'))
            ->assertOk()
            ->assertSee('Canales WhatsApp')
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('Checklist Meta')
            ->assertSee('Sin canales WhatsApp');
    }

    public function test_company_admin_can_create_commercial_whatsapp_channel(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-crear@example.com',
            'company_name' => 'Empresa WhatsApp Crear',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Crear')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();

        $this->post(route('whatsapp.channels.store'), [
            'name' => 'WhatsApp Principal',
            'branch_id' => $branch->id,
            'is_active' => '1',
            'settings' => [
                'api_base_url' => 'https://graph.facebook.com/v21.0',
                'phone_number_id' => '123456789012',
                'business_account_id' => '987654321098',
                'access_token' => 'token_comercial_1234567890',
                'webhook_verify_token' => 'verify-token',
                'app_secret' => 'app_secret_1234567890',
                'access_token_expires_at' => '2026-12-31',
                'access_token_rotated_at' => '2026-06-19',
                'access_token_responsible' => 'Equipo Comercial',
                'access_token_rotation_procedure' => 'Rotar token y validar envio.',
            ],
        ])->assertRedirect(route('whatsapp.channels.index'));

        $channel = Channel::query()
            ->where('company_id', $company->id)
            ->where('branch_id', $branch->id)
            ->where('slug', 'whatsapp')
            ->firstOrFail();

        $this->assertSame('WhatsApp Principal', $channel->name);
        $this->assertTrue($channel->is_active);
        $this->assertSame('whatsapp_cloud', data_get($channel->settings, 'provider'));
        $this->assertSame('123456789012', data_get($channel->settings, 'phone_number_id'));
        $this->assertSame('987654321098', data_get($channel->settings, 'business_account_id'));
        $this->assertSame('token_comercial_1234567890', data_get($channel->settings, 'access_token'));
        $this->assertSame('verify-token', data_get($channel->settings, 'webhook_verify_token'));
    }

    public function test_company_admin_can_update_commercial_whatsapp_channel_without_replacing_secrets(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-editar@example.com',
            'company_name' => 'Empresa WhatsApp Editar',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Editar')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Viejo',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'access_token' => 'token_original_1234567890',
                'webhook_verify_token' => 'verify-original',
                'app_secret' => 'app_secret_original',
            ],
        ]);

        $this->patch(route('whatsapp.channels.update', $channel), [
            'name' => 'WhatsApp Actualizado',
            'branch_id' => $branch->id,
            'is_active' => '1',
            'settings' => [
                'api_base_url' => 'https://graph.facebook.com/v21.0',
                'phone_number_id' => '111222333444',
                'business_account_id' => '444333222111',
                'access_token' => '',
                'webhook_verify_token' => '',
                'app_secret' => '',
                'access_token_responsible' => 'Mesa de ayuda',
            ],
        ])->assertRedirect(route('whatsapp.channels.index'));

        $channel->refresh();

        $this->assertSame('WhatsApp Actualizado', $channel->name);
        $this->assertSame('111222333444', data_get($channel->settings, 'phone_number_id'));
        $this->assertSame('444333222111', data_get($channel->settings, 'business_account_id'));
        $this->assertSame('token_original_1234567890', data_get($channel->settings, 'access_token'));
        $this->assertSame('verify-original', data_get($channel->settings, 'webhook_verify_token'));
        $this->assertSame('app_secret_original', data_get($channel->settings, 'app_secret'));
        $this->assertSame('Mesa de ayuda', data_get($channel->settings, 'access_token_responsible'));
    }

    public function test_operator_cannot_create_commercial_whatsapp_channel(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-operador-post@example.com',
            'company_name' => 'Empresa WhatsApp Operador Post',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Operador Post')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $operatorRole = Role::query()->firstOrCreate(['name' => 'operator'], ['label' => 'Operator']);
        $operator = User::factory()->create(['email' => 'operador-whatsapp-post@example.com']);
        $operator->roles()->attach($operatorRole->id);
        Membership::query()->create([
            'user_id' => $operator->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $operatorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->post(route('whatsapp.channels.store'), [
                'name' => 'WhatsApp Operador',
                'branch_id' => $branch->id,
                'is_active' => '1',
            ])
            ->assertForbidden();
    }

    public function test_company_admin_can_test_commercial_whatsapp_channel_connection(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-test@example.com',
            'company_name' => 'Empresa WhatsApp Test',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Test')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Test',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'api_base_url' => 'https://graph.facebook.test/v21.0',
                'phone_number_id' => '123456789012',
                'business_account_id' => '987654321098',
                'access_token' => 'token_test_1234567890',
            ],
        ]);

        Http::fake([
            'graph.facebook.test/v21.0/123456789012*' => Http::response([
                'id' => '123456789012',
                'display_phone_number' => '+57 300 000 0000',
                'verified_name' => 'Empresa Test',
            ]),
            'graph.facebook.test/v21.0/987654321098*' => Http::response([
                'id' => '987654321098',
                'name' => 'WABA Test',
            ]),
        ]);

        $this->post(route('whatsapp.channels.test', $channel))
            ->assertRedirect(route('whatsapp.channels.index'))
            ->assertSessionHas('status', 'Conexion con Meta validada.');

        $channel->refresh();

        $this->assertSame('Empresa Test', data_get($channel->settings, 'last_connection_test.verified_name'));
        $this->assertSame('WABA Test', data_get($channel->settings, 'last_connection_test.business_name'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/123456789012'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/987654321098'));
    }

    public function test_commercial_whatsapp_connection_reports_missing_credentials(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-missing@example.com',
            'company_name' => 'Empresa WhatsApp Missing',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Missing')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Missing',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => ['provider' => 'whatsapp_cloud'],
        ]);

        $this->post(route('whatsapp.channels.test', $channel))
            ->assertRedirect(route('whatsapp.channels.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Faltan credenciales'));
    }

    public function test_company_admin_can_sync_templates_from_commercial_whatsapp_channel(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-sync@example.com',
            'company_name' => 'Empresa WhatsApp Sync',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Sync')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Sync',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'api_base_url' => 'https://graph.facebook.test/v21.0',
                'business_account_id' => '987654321098',
                'access_token' => 'token_sync_1234567890',
            ],
        ]);

        Http::fake([
            'graph.facebook.test/v21.0/987654321098/message_templates*' => Http::response([
                'data' => [[
                    'id' => 'meta-template-comercial',
                    'name' => 'bienvenida_comercial',
                    'language' => 'es',
                    'status' => 'APPROVED',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'Hola {{1}}, bienvenido.'],
                    ],
                ]],
            ]),
        ]);

        $this->post(route('whatsapp.channels.sync-templates', $channel))
            ->assertRedirect(route('whatsapp.channels.index'))
            ->assertSessionHas('status', fn (string $message) => str_contains($message, '1 plantillas'));

        $template = MessageTemplate::query()->where('meta_template_id', 'meta-template-comercial')->firstOrFail();

        $this->assertSame($channel->id, $template->channel_id);
        $this->assertSame($company->id, $template->company_id);
        $this->assertSame($branch->id, $template->branch_id);
        $this->assertSame('Hola {{1}}, bienvenido.', $template->currentVersion->body);
    }

    public function test_commercial_whatsapp_screen_shows_operational_status(): void
    {
        $this->post('/register', [
            'name' => 'Usuario Comercial',
            'email' => 'comercial-whatsapp-estado@example.com',
            'company_name' => 'Empresa WhatsApp Estado',
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $company = Company::query()->where('name', 'Empresa WhatsApp Estado')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();
        $secondBranch = Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'Secundaria',
            'code' => 'secundaria',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);

        Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Incompleto',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => ['provider' => 'whatsapp_cloud'],
        ]);

        $readyChannel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $secondBranch->id,
            'name' => 'WhatsApp Listo',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'phone_number_id' => '123456789012',
                'business_account_id' => '987654321098',
                'access_token' => 'token_estado_1234567890',
                'webhook_verify_token' => 'verify-estado',
                'access_token_expires_at' => now()->addDays(60)->format('Y-m-d'),
                'last_connection_test' => [
                    'verified_name' => 'Empresa Estado',
                    'tested_at' => now()->toISOString(),
                ],
            ],
        ]);
        $readyChannel->update(['branch_id' => null]);

        $this->get(route('whatsapp.channels.index'))
            ->assertOk()
            ->assertSee('Configuracion incompleta')
            ->assertSee('Configurar Phone Number ID')
            ->assertSee('Falta probar conexion con Meta.')
            ->assertSee('Listo para operar')
            ->assertSee('Ultima conexion validada');
    }

    public function test_commercial_whatsapp_channels_are_isolated_between_companies(): void
    {
        [$adminA, $companyA, $branchA] = $this->registerTrialCompany('Admin A', 'admin-a@example.com', 'Empresa A WA');
        [$adminB, $companyB, $branchB] = $this->registerTrialCompany('Admin B', 'admin-b@example.com', 'Empresa B WA');

        $channelA = Channel::query()->create([
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'name' => 'WhatsApp Empresa A',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'phone_number_id' => '111111111111',
            ],
        ]);

        $channelB = Channel::query()->create([
            'company_id' => $companyB->id,
            'branch_id' => $branchB->id,
            'name' => 'WhatsApp Empresa B',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'phone_number_id' => '222222222222',
            ],
        ]);

        $this->actingAs($adminA)
            ->withSession($this->tenantSession($adminA))
            ->get(route('whatsapp.channels.index'))
            ->assertOk()
            ->assertSee('WhatsApp Empresa A')
            ->assertDontSee('WhatsApp Empresa B')
            ->assertDontSee('222222222222');

        $this->actingAs($adminB)
            ->withSession($this->tenantSession($adminB))
            ->get(route('whatsapp.channels.index'))
            ->assertOk()
            ->assertSee('WhatsApp Empresa B')
            ->assertDontSee('WhatsApp Empresa A')
            ->assertDontSee('111111111111');

        $this->assertNotSame($channelA->id, $channelB->id);
    }

    public function test_company_admin_cannot_manage_other_company_whatsapp_channel_by_direct_url(): void
    {
        [$adminA] = $this->registerTrialCompany('Admin Cruzado A', 'admin-cruzado-a@example.com', 'Empresa Cruzada A');
        [, $companyB, $branchB] = $this->registerTrialCompany('Admin Cruzado B', 'admin-cruzado-b@example.com', 'Empresa Cruzada B');

        $foreignChannel = Channel::query()->create([
            'company_id' => $companyB->id,
            'branch_id' => $branchB->id,
            'name' => 'WhatsApp Ajeno',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'api_base_url' => 'https://graph.facebook.test/v21.0',
                'phone_number_id' => '333333333333',
                'business_account_id' => '444444444444',
                'access_token' => 'token_ajeno_1234567890',
            ],
        ]);

        $this->actingAs($adminA)
            ->withSession($this->tenantSession($adminA))
            ->patch(route('whatsapp.channels.update', $foreignChannel), [
                'name' => 'Intento Cruzado',
                'branch_id' => $branchB->id,
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($adminA)
            ->withSession($this->tenantSession($adminA))
            ->post(route('whatsapp.channels.test', $foreignChannel))
            ->assertForbidden();

        $this->actingAs($adminA)
            ->withSession($this->tenantSession($adminA))
            ->post(route('whatsapp.channels.sync-templates', $foreignChannel))
            ->assertForbidden();

        $foreignChannel->refresh();

        $this->assertSame('WhatsApp Ajeno', $foreignChannel->name);
        $this->assertNull(data_get($foreignChannel->settings, 'last_connection_test'));
    }

    public function test_company_admin_cannot_create_whatsapp_channel_for_other_company_branch(): void
    {
        [$adminA] = $this->registerTrialCompany('Admin Sede A', 'admin-sede-a@example.com', 'Empresa Sede A');
        [, , $branchB] = $this->registerTrialCompany('Admin Sede B', 'admin-sede-b@example.com', 'Empresa Sede B');

        $this->actingAs($adminA)
            ->withSession($this->tenantSession($adminA))
            ->post(route('whatsapp.channels.store'), [
                'name' => 'WhatsApp Sede Ajena',
                'branch_id' => $branchB->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('branch_id');
    }

    public function test_commercial_whatsapp_real_validation_command_succeeds_with_fake_meta(): void
    {
        [, $company, $branch] = $this->registerTrialCompany('Admin Comando', 'admin-comando@example.com', 'Empresa Comando WA');
        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'name' => 'WhatsApp Comando',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'api_base_url' => 'https://graph.facebook.test/v21.0',
                'phone_number_id' => '555555555555',
                'business_account_id' => '666666666666',
                'access_token' => 'token_comando_1234567890',
            ],
        ]);

        Http::fake([
            'graph.facebook.test/v21.0/555555555555*' => Http::response([
                'id' => '555555555555',
                'display_phone_number' => '+57 300 555 5555',
                'verified_name' => 'Empresa Comando',
            ]),
            'graph.facebook.test/v21.0/666666666666/message_templates*' => Http::response([
                'data' => [[
                    'id' => 'meta-template-command',
                    'name' => 'validacion_comando',
                    'language' => 'es',
                    'status' => 'APPROVED',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'BODY', 'text' => 'Validacion {{1}}.'],
                    ],
                ]],
            ]),
            'graph.facebook.test/v21.0/666666666666*' => Http::response([
                'id' => '666666666666',
                'name' => 'WABA Comando',
            ]),
        ]);

        $this->artisan('noiachat:whatsapp-commercial-validate', [
            'channel_id' => $channel->id,
            '--sync-templates' => true,
        ])->assertExitCode(0);

        $channel->refresh();

        $this->assertSame('Empresa Comando', data_get($channel->settings, 'last_connection_test.verified_name'));
        $this->assertDatabaseHas('message_templates', [
            'channel_id' => $channel->id,
            'meta_template_id' => 'meta-template-command',
            'name' => 'validacion_comando',
        ]);
    }

    private function registerTrialCompany(string $name, string $email, string $companyName): array
    {
        auth()->guard()->logout();
        $this->app['session']->flush();

        $this->post('/register', [
            'name' => $name,
            'email' => $email,
            'company_name' => $companyName,
            'branch_name' => 'Principal',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', $email)->firstOrFail();
        $company = Company::query()->where('name', $companyName)->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->firstOrFail();

        return [$user, $company, $branch];
    }

    private function tenantSession(User $user): array
    {
        $membership = $user->memberships()->firstOrFail();

        return [
            'tenant.membership_id' => $membership->id,
            'tenant.company_id' => $membership->company_id,
            'tenant.branch_id' => $membership->branch_id,
        ];
    }
}
