<?php

namespace Tests\Feature;

use App\Modules\Billing\Application\Services\SubscriptionFeatureService;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Feature;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Billing\Infrastructure\Persistence\Models\SubscriptionChangeRequest;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Models\User;
use Database\Seeders\BillingSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\TenancySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BillingPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_seeder_creates_plans_features_and_matrix(): void
    {
        $this->seed(BillingSeeder::class);

        $trial = Plan::query()->where('code', 'basic_trial')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();

        $this->assertSame(14, $trial->trial_days);
        $this->assertSame(3, $trial->max_users);
        $this->assertSame(1, $trial->max_branches);
        $this->assertSame(100, $trial->max_contacts);
        $this->assertSame(1, $trial->max_whatsapp_channels);
        $this->assertSame('Prueba inicial', data_get($trial->metadata, 'commercial_label'));
        $this->assertSame('Plan recomendado', data_get($pro->metadata, 'commercial_label'));

        $this->assertDatabaseHas('features', ['code' => 'contacts.import']);
        $this->assertDatabaseHas('features', ['code' => 'reports.export']);

        $trialFeatureCodes = $trial->features()->pluck('code')->all();
        $proFeatureCodes = $pro->features()->pluck('code')->all();

        $this->assertContains('contacts.create', $trialFeatureCodes);
        $this->assertNotContains('contacts.import', $trialFeatureCodes);
        $this->assertContains('contacts.import', $proFeatureCodes);
        $this->assertContains('reports.export', $proFeatureCodes);
    }

    public function test_enterprise_plan_includes_all_active_features(): void
    {
        $this->seed(BillingSeeder::class);

        $enterprise = Plan::query()->where('code', 'enterprise')->firstOrFail();

        $this->assertSame(
            Feature::query()->where('is_active', true)->count(),
            $enterprise->features()->count(),
        );
    }

    public function test_subscription_feature_service_allows_features_in_active_trial(): void
    {
        $this->seed(TenancySeeder::class);
        $this->seed(BillingSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $service = app(SubscriptionFeatureService::class);

        $this->assertTrue($service->operational($company));
        $this->assertTrue($service->allows($company, 'contacts.create'));
        $this->assertFalse($service->allows($company, 'contacts.import'));
        $this->assertSame(3, $service->limit($company, 'users'));
        $this->assertSame(1, $service->limit($company, 'branches'));
        $this->assertSame(100, $service->limit($company, 'contacts'));
        $this->assertSame(1, $service->limit($company, 'whatsapp_channels'));
    }

    public function test_subscription_feature_service_blocks_expired_trial(): void
    {
        $this->seed(TenancySeeder::class);
        $this->seed(BillingSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        CompanySubscription::query()->where('company_id', $company->id)->update([
            'trial_ends_at' => now()->subDay(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        $service = app(SubscriptionFeatureService::class);

        $this->assertFalse($service->operational($company));
        $this->assertFalse($service->allows($company, 'contacts.create'));
        $this->assertSame(0, $service->remainingTrialDays($company));
    }

    public function test_subscription_feature_service_allows_active_paid_plan(): void
    {
        $this->seed(TenancySeeder::class);
        $this->seed(BillingSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();

        CompanySubscription::query()->where('company_id', $company->id)->delete();
        CompanySubscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $pro->id,
            'status' => 'active',
            'current_period_started_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
        ]);

        $service = app(SubscriptionFeatureService::class);

        $this->assertTrue($service->operational($company));
        $this->assertTrue($service->allows($company, 'contacts.import'));
        $this->assertTrue($service->allows($company, 'reports.export'));
        $this->assertSame(15, $service->limit($company, 'users'));
        $this->assertSame(5, $service->limit($company, 'branches'));
    }

    public function test_feature_middleware_allows_route_when_plan_includes_feature(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_feature_middleware_blocks_route_when_plan_does_not_include_feature(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $trial = Plan::query()->where('code', 'basic_trial')->firstOrFail();
        $feature = Feature::query()->where('code', 'users.manage')->firstOrFail();

        DB::table('plan_features')
            ->where('plan_id', $trial->id)
            ->where('feature_id', $feature->id)
            ->update(['enabled' => false]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertForbidden()
            ->assertSee('Tu plan actual no incluye esta funcionalidad.');
    }

    public function test_feature_middleware_allows_super_admin_bypass(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $superAdminRole = Role::query()
            ->where('name', 'super_admin')
            ->firstOrFail();
        $trial = Plan::query()->where('code', 'basic_trial')->firstOrFail();
        $feature = Feature::query()->where('code', 'users.manage')->firstOrFail();

        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        DB::table('plan_features')
            ->where('plan_id', $trial->id)
            ->where('feature_id', $feature->id)
            ->update(['enabled' => false]);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_user_creation_is_blocked_when_plan_user_limit_is_reached(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();

        foreach (['limite-uno@noiachat.local', 'limite-dos@noiachat.local'] as $email) {
            $user = User::factory()->create(['email' => $email]);
            Membership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'role_id' => $operatorRole->id,
                'is_default' => false,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Usuario Excedido',
                'email' => 'excedido@noiachat.local',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => '1',
                'roles' => [$operatorRole->id],
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_super_admin_can_create_user_over_plan_limit(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        foreach (['super-limite-uno@noiachat.local', 'super-limite-dos@noiachat.local'] as $email) {
            $user = User::factory()->create(['email' => $email]);
            Membership::query()->create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'role_id' => $operatorRole->id,
                'is_default' => false,
                'is_active' => true,
            ]);
        }

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Usuario Soporte',
                'email' => 'soporte-overlimit@noiachat.local',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'is_active' => '1',
                'roles' => [$operatorRole->id],
            ])
            ->assertRedirect(route('users.index'));
    }

    public function test_branch_creation_is_blocked_when_plan_branch_limit_is_reached(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('tenancy.branches.store'), [
                'name' => 'Sede excedida',
                'code' => 'sede-excedida',
                'city' => 'Bogota',
                'timezone' => 'America/Bogota',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_contact_creation_is_blocked_when_plan_contact_limit_is_reached(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();

        for ($index = 1; $index <= 100; $index++) {
            Contact::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'first_name' => "Contacto {$index}",
                'full_name' => "Contacto {$index}",
                'primary_phone' => '+57300000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);
        }

        $this->actingAs($admin)
            ->post(route('contacts.store'), [
                'first_name' => 'Contacto excedido',
                'primary_phone' => '+573009999999',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('primary_phone');
    }

    public function test_whatsapp_channel_activation_is_blocked_when_plan_channel_limit_is_reached(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();

        $channel = Channel::query()->create([
            'company_id' => $company->id,
            'branch_id' => null,
            'name' => 'WhatsApp secundario',
            'slug' => 'whatsapp-secondary',
            'is_active' => false,
            'settings' => ['provider' => 'whatsapp_cloud'],
        ]);

        $this->actingAs($admin)
            ->put(route('settings.channels.update', $channel), [
                'name' => 'WhatsApp secundario',
                'is_active' => '1',
                'settings' => ['provider' => 'whatsapp_cloud'],
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($channel->fresh()->is_active);
    }

    public function test_subscriptions_check_command_expires_trials_and_audits_change(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $subscription = CompanySubscription::query()->where('company_id', $company->id)->firstOrFail();

        $subscription->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->artisan('noiachat:subscriptions-check')
            ->expectsOutput('Trials vencidos actualizados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $subscription->id,
            'status' => 'expired',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'action' => 'update',
            'module' => 'billing',
            'target_type' => CompanySubscription::class,
            'target_id' => (string) $subscription->id,
        ]);
    }

    public function test_subscriptions_check_dry_run_does_not_expire_trials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $subscription = CompanySubscription::query()->where('company_id', $company->id)->firstOrFail();

        $subscription->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->subDay(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->artisan('noiachat:subscriptions-check --dry-run')
            ->expectsOutput('Trials vencidos detectados: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $subscription->id,
            'status' => 'trialing',
        ]);

        $this->assertDatabaseMissing('audit_logs', [
            'module' => 'billing',
            'target_id' => (string) $subscription->id,
        ]);
    }

    public function test_expired_trial_allows_dashboard_but_blocks_operational_feature_routes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();

        CompanySubscription::query()->where('company_id', $company->id)->update([
            'status' => 'expired',
            'trial_ends_at' => now()->subDay(),
            'current_period_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('El periodo de prueba vencio. Renueva o cambia de plan para continuar operando.');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertForbidden()
            ->assertSee('El periodo de prueba vencio. Renueva o cambia de plan para continuar operando.');
    }

    public function test_dashboard_warns_when_trial_is_close_to_expiring(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();

        CompanySubscription::query()->where('company_id', $company->id)->update([
            'status' => 'trialing',
            'trial_ends_at' => now()->addDays(2),
            'current_period_ends_at' => now()->addDays(2),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tu prueba vence en');
    }

    public function test_company_admin_can_view_billing_panel_with_plan_limits_and_features(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Plan basico de prueba')
            ->assertSee('Limites del plan')
            ->assertSee('Usuarios')
            ->assertSee('Crear contactos')
            ->assertSee('Catalogo comercial')
            ->assertSee('Solicitar cambio de plan');
    }

    public function test_operator_cannot_view_billing_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $operator = User::factory()->create();
        $operator->roles()->attach($operatorRole);

        $this->actingAs($operator)
            ->get(route('billing.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_update_company_subscription_from_billing_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        $admin->load('roles');

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();
        $subscription = CompanySubscription::query()->where('company_id', $company->id)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('billing.subscription.update'), [
                'company_id' => $company->id,
                'plan_id' => $pro->id,
                'status' => 'active',
                'trial_ends_at' => null,
                'current_period_ends_at' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $subscription->id,
            'company_id' => $company->id,
            'plan_id' => $pro->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'update',
            'module' => 'billing',
            'target_type' => CompanySubscription::class,
            'target_id' => (string) $subscription->id,
        ]);
    }

    public function test_company_admin_can_request_plan_change(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();
        $currentPlan = CompanySubscription::query()->where('company_id', $company->id)->firstOrFail()->plan;

        $this->actingAs($admin)
            ->post(route('billing.change-requests.store'), [
                'requested_plan_id' => $pro->id,
                'message' => 'Necesitamos importaciones y reportes.',
            ])
            ->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('subscription_change_requests', [
            'company_id' => $company->id,
            'requested_by' => $admin->id,
            'current_plan_id' => $currentPlan->id,
            'requested_plan_id' => $pro->id,
            'status' => 'pending',
        ]);

        $request = SubscriptionChangeRequest::query()->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'create',
            'module' => 'billing',
            'target_type' => SubscriptionChangeRequest::class,
            'target_id' => (string) $request->id,
        ]);
    }

    public function test_super_admin_can_approve_plan_change_request(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();
        $admin->roles()->syncWithoutDetaching([$superAdminRole->id]);
        $admin->load('roles');

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();
        $subscription = CompanySubscription::query()->where('company_id', $company->id)->firstOrFail();
        $changeRequest = SubscriptionChangeRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $admin->id,
            'current_plan_id' => $subscription->plan_id,
            'requested_plan_id' => $pro->id,
            'status' => 'pending',
            'message' => 'Aprobar Pro.',
        ]);

        $this->actingAs($admin)
            ->patch(route('billing.change-requests.resolve', $changeRequest), [
                'decision' => 'approved',
                'admin_notes' => 'Aprobado por comercial.',
            ])
            ->assertRedirect(route('billing.index'));

        $this->assertDatabaseHas('subscription_change_requests', [
            'id' => $changeRequest->id,
            'status' => 'approved',
            'resolved_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('company_subscriptions', [
            'id' => $subscription->id,
            'company_id' => $company->id,
            'plan_id' => $pro->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'action' => 'update',
            'module' => 'billing',
            'target_type' => SubscriptionChangeRequest::class,
            'target_id' => (string) $changeRequest->id,
        ]);
    }
}
