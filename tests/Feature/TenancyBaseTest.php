<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Application\DTOs\SendTextMessageDTO;
use App\Modules\Messaging\Infrastructure\Integrations\WhatsAppCloudApiProvider;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Webhooks\Application\UseCases\ProcessWhatsAppWebhookUseCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenancyBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_default_company_branch_and_admin_membership(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();

        $this->assertDatabaseHas('memberships', [
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($admin->memberships()->exists());
        $this->assertTrue($admin->companies()->where('companies.id', $company->id)->exists());
    }

    public function test_company_has_branches_and_memberships(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();

        $this->assertTrue($company->branches()->where('code', 'principal')->exists());
        $this->assertTrue($company->memberships()->whereHas('user', fn ($query) => $query->where('email', 'admin@noiachat.local'))->exists());
    }

    public function test_user_can_have_memberships_in_multiple_companies(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $roleId = $admin->roles()->where('name', 'admin')->firstOrFail()->id;
        $company = Company::create([
            'name' => 'Empresa secundaria',
            'slug' => 'empresa-secundaria',
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede secundaria',
            'code' => 'secundaria',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);

        Membership::create([
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $roleId,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->assertCount(2, $admin->fresh()->companies);
    }

    public function test_operational_tables_have_tenant_columns(): void
    {
        foreach ([
            'channels',
            'contacts',
            'contact_channels',
            'contact_consents',
            'contact_blacklist',
            'conversations',
            'message_templates',
            'media_files',
            'messages',
            'message_events',
            'message_attachments',
            'inbound_messages',
            'opt_out_requests',
            'provider_logs',
            'audit_logs',
        ] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'company_id'), "{$table} must have company_id.");
            $this->assertTrue(Schema::hasColumn($table, 'branch_id'), "{$table} must have branch_id.");
        }
    }

    public function test_seeded_operational_data_is_attached_to_default_tenant(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();

        foreach (['channels', 'contacts', 'contact_channels', 'contact_consents', 'message_templates'] as $table) {
            $this->assertSame(0, DB::table($table)->whereNull('company_id')->count(), "{$table} has rows without company_id.");
            $this->assertSame(0, DB::table($table)->whereNull('branch_id')->count(), "{$table} has rows without branch_id.");
            $this->assertTrue(DB::table($table)->where('company_id', $company->id)->where('branch_id', $branch->id)->exists(), "{$table} has no default tenant rows.");
        }
    }

    public function test_tenant_context_is_resolved_for_authenticated_requests(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $membership = $admin->memberships()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Empresa activa')
            ->assertSessionHas('tenant.membership_id', $membership->id)
            ->assertSessionHas('tenant.company_id', $membership->company_id)
            ->assertSessionHas('tenant.branch_id', $membership->branch_id);
    }

    public function test_user_can_switch_active_tenant_membership(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $roleId = $admin->roles()->where('name', 'admin')->firstOrFail()->id;
        $company = Company::create([
            'name' => 'Empresa norte',
            'slug' => 'empresa-norte',
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte',
            'code' => 'norte',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $membership = Membership::create([
            'user_id' => $admin->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $roleId,
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('tenant-context.update'), ['membership_id' => $membership->id])
            ->assertRedirect()
            ->assertSessionHas('tenant.membership_id', $membership->id)
            ->assertSessionHas('tenant.company_id', $company->id)
            ->assertSessionHas('tenant.branch_id', $branch->id);
    }

    public function test_user_cannot_switch_to_membership_from_another_user(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $operator = User::factory()->create(['email' => 'tenant-operator@noiachat.local']);
        $operatorRole = \App\Modules\Users\Infrastructure\Persistence\Models\Role::query()->where('name', 'operator')->firstOrFail();
        $operator->roles()->attach($operatorRole->id);

        $this->actingAs($operator)
            ->post(route('tenant-context.update'), ['membership_id' => $admin->memberships()->firstOrFail()->id])
            ->assertSessionHasErrors('membership_id');
    }

    public function test_legacy_user_with_role_gets_default_membership_on_request(): void
    {
        $this->seed(DatabaseSeeder::class);

        $operator = User::factory()->create(['email' => 'legacy-operator@noiachat.local']);
        $operatorRole = \App\Modules\Users\Infrastructure\Persistence\Models\Role::query()->where('name', 'operator')->firstOrFail();
        $operator->roles()->attach($operatorRole->id);

        $this->assertFalse($operator->memberships()->exists());

        $this->actingAs($operator)->get(route('dashboard'))->assertOk();

        $this->assertTrue($operator->fresh()->memberships()->where('is_active', true)->exists());
    }

    public function test_conversation_list_is_scoped_to_active_company(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $defaultCompany = Company::query()->where('slug', 'default')->firstOrFail();
        $defaultBranch = Branch::query()->where('company_id', $defaultCompany->id)->where('code', 'principal')->firstOrFail();
        $other = $this->createCompanyWithBranch('otra-empresa', 'Otra empresa', 'principal');
        $channel = \App\Modules\Contacts\Infrastructure\Persistence\Models\Channel::query()->firstOrFail();

        $visibleContact = Contact::create([
            'company_id' => $defaultCompany->id,
            'branch_id' => $defaultBranch->id,
            'first_name' => 'Visible',
            'full_name' => 'Visible Tenant',
            'primary_phone' => '573000000001',
            'status' => 'active',
        ]);
        Conversation::create([
            'company_id' => $defaultCompany->id,
            'branch_id' => $defaultBranch->id,
            'contact_id' => $visibleContact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $hiddenContact = Contact::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'first_name' => 'Oculto',
            'full_name' => 'Oculto Tenant',
            'primary_phone' => '573000000002',
            'status' => 'active',
        ]);
        Conversation::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'contact_id' => $hiddenContact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Visible Tenant')
            ->assertDontSee('Oculto Tenant');
    }

    public function test_user_cannot_open_conversation_from_another_company(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $other = $this->createCompanyWithBranch('empresa-cerrada', 'Empresa cerrada', 'principal');
        $channel = \App\Modules\Contacts\Infrastructure\Persistence\Models\Channel::query()->firstOrFail();
        $contact = Contact::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'first_name' => 'Ajeno',
            'full_name' => 'Ajeno Tenant',
            'primary_phone' => '573000000003',
            'status' => 'active',
        ]);
        $conversation = Conversation::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('conversations.show', $conversation))
            ->assertForbidden();
    }

    public function test_company_membership_can_filter_conversation_inbox_by_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $north = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte inbox',
            'code' => 'norte-inbox',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $south = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede sur inbox',
            'code' => 'sur-inbox',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $companyAdmin = User::factory()->create(['email' => 'company-inbox@noiachat.local']);
        $companyAdmin->roles()->attach($role->id);
        Membership::create([
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();

        $northContact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'first_name' => 'Norte',
            'full_name' => 'Inbox Norte',
            'primary_phone' => '573000000030',
            'status' => 'active',
        ]);
        $southContact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'first_name' => 'Sur',
            'full_name' => 'Inbox Sur',
            'primary_phone' => '573000000031',
            'status' => 'active',
        ]);
        Conversation::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'contact_id' => $northContact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        Conversation::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'contact_id' => $southContact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now()->subMinute(),
        ]);

        $this->actingAs($companyAdmin)
            ->get(route('conversations.index', ['branch_id' => $north->id]))
            ->assertOk()
            ->assertSee('Inbox Norte')
            ->assertDontSee('Inbox Sur');
    }

    public function test_conversation_assignment_rejects_user_from_another_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $north = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte asignacion',
            'code' => 'norte-asignacion',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $south = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede sur asignacion',
            'code' => 'sur-asignacion',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $companyAdmin = User::factory()->create(['email' => 'company-assignment@noiachat.local']);
        $companyAdmin->roles()->attach($adminRole->id);
        Membership::create([
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $adminRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $northOperator = User::factory()->create(['email' => 'north-assignment@noiachat.local']);
        $northOperator->roles()->attach($operatorRole->id);
        Membership::create([
            'user_id' => $northOperator->id,
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'role_id' => $operatorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $southOperator = User::factory()->create(['email' => 'south-assignment@noiachat.local']);
        $southOperator->roles()->attach($operatorRole->id);
        Membership::create([
            'user_id' => $southOperator->id,
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'role_id' => $operatorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();
        $contact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'first_name' => 'Asignacion',
            'full_name' => 'Asignacion Norte',
            'primary_phone' => '573000000032',
            'status' => 'active',
        ]);
        $conversation = Conversation::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($companyAdmin)->put(route('conversations.assign', $conversation), [
            'assigned_user_id' => $southOperator->id,
            'status' => 'pending',
        ])->assertForbidden();

        $this->actingAs($companyAdmin)->put(route('conversations.assign', $conversation), [
            'assigned_user_id' => $northOperator->id,
            'status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'assigned_user_id' => $northOperator->id,
            'status' => 'pending',
        ]);
    }

    public function test_branch_membership_cannot_see_other_branch_contacts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $north = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte',
            'code' => 'norte',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $south = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede sur',
            'code' => 'sur',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $operator = User::factory()->create(['email' => 'branch-operator@noiachat.local']);
        $role = Role::query()->where('name', 'operator')->firstOrFail();
        $operator->roles()->attach($role->id);
        Membership::create([
            'user_id' => $operator->id,
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        Contact::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'first_name' => 'Norte',
            'full_name' => 'Cliente Norte',
            'primary_phone' => '573000000004',
            'status' => 'active',
        ]);
        Contact::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'first_name' => 'Sur',
            'full_name' => 'Cliente Sur',
            'primary_phone' => '573000000005',
            'status' => 'active',
        ]);

        $this->actingAs($operator)
            ->get(route('contacts.index'))
            ->assertOk()
            ->assertSee('Cliente Norte')
            ->assertDontSee('Cliente Sur');
    }

    public function test_dashboard_counts_only_active_tenant_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $defaultCompany = Company::query()->where('slug', 'default')->firstOrFail();
        $defaultBranch = Branch::query()->where('company_id', $defaultCompany->id)->where('code', 'principal')->firstOrFail();
        $other = $this->createCompanyWithBranch('conteos-externos', 'Conteos externos', 'principal');
        $channel = \App\Modules\Contacts\Infrastructure\Persistence\Models\Channel::query()->firstOrFail();
        $visibleContact = Contact::create([
            'company_id' => $defaultCompany->id,
            'branch_id' => $defaultBranch->id,
            'first_name' => 'Conteo',
            'full_name' => 'Conteo Visible',
            'primary_phone' => '573000000006',
            'status' => 'active',
        ]);
        $hiddenContact = Contact::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'first_name' => 'Conteo',
            'full_name' => 'Conteo Oculto',
            'primary_phone' => '573000000007',
            'status' => 'active',
        ]);

        Message::create([
            'company_id' => $defaultCompany->id,
            'branch_id' => $defaultBranch->id,
            'contact_id' => $visibleContact->id,
            'channel_id' => $channel->id,
            'type' => 'text',
            'status' => 'sent',
            'body' => 'Visible',
        ]);
        Message::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'contact_id' => $hiddenContact->id,
            'channel_id' => $channel->id,
            'type' => 'text',
            'status' => 'sent',
            'body' => 'Oculto',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Mensajes enviados', '1', 'Mensajes fallidos']);
    }

    public function test_company_membership_can_filter_dashboard_metrics_by_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $north = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte metricas',
            'code' => 'norte-metricas',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $south = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede sur metricas',
            'code' => 'sur-metricas',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $companyAdmin = User::factory()->create(['email' => 'company-dashboard@noiachat.local']);
        $companyAdmin->roles()->attach($role->id);
        Membership::create([
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();
        $northContact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'first_name' => 'Metricas',
            'full_name' => 'Metricas Norte',
            'primary_phone' => '573000000040',
            'status' => 'active',
        ]);
        $southContact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'first_name' => 'Metricas',
            'full_name' => 'Metricas Sur',
            'primary_phone' => '573000000041',
            'status' => 'active',
        ]);
        Message::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'contact_id' => $northContact->id,
            'channel_id' => $channel->id,
            'type' => 'text',
            'status' => 'sent',
            'body' => 'Norte',
        ]);
        Message::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'contact_id' => $southContact->id,
            'channel_id' => $channel->id,
            'type' => 'text',
            'status' => 'failed',
            'body' => 'Sur',
        ]);
        InboundMessage::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'contact_id' => $northContact->id,
            'channel_id' => $channel->id,
            'provider_message_id' => 'wamid-dashboard-north',
            'from_phone' => $northContact->primary_phone,
            'body' => 'Inbound norte',
            'payload' => [],
        ]);

        $this->actingAs($companyAdmin)
            ->get(route('dashboard', ['branch_id' => $north->id]))
            ->assertOk()
            ->assertSeeInOrder(['Total contactos', '1', 'Mensajes enviados', '1', 'Mensajes fallidos', '0', 'Mensajes recibidos', '1']);
    }

    public function test_company_membership_can_filter_audit_logs_by_branch(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $north = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede norte auditoria',
            'code' => 'norte-auditoria',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $south = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede sur auditoria',
            'code' => 'sur-auditoria',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $role = Role::query()->where('name', 'auditor')->firstOrFail();
        $auditor = User::factory()->create(['email' => 'company-audit@noiachat.local']);
        $auditor->roles()->attach($role->id);
        Membership::create([
            'user_id' => $auditor->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        AuditLog::create([
            'company_id' => $company->id,
            'branch_id' => $north->id,
            'user_id' => $auditor->id,
            'action' => 'update',
            'module' => 'north_audit_module',
            'target_type' => Contact::class,
            'target_id' => 'north-target',
        ]);
        AuditLog::create([
            'company_id' => $company->id,
            'branch_id' => $south->id,
            'user_id' => $auditor->id,
            'action' => 'update',
            'module' => 'south_audit_module',
            'target_type' => Contact::class,
            'target_id' => 'south-target',
        ]);

        $this->actingAs($auditor)
            ->get(route('audit-logs.index', ['branch_id' => $north->id]))
            ->assertOk()
            ->assertSee('north_audit_module')
            ->assertDontSee('south_audit_module');
    }

    public function test_whatsapp_webhook_resolves_channel_and_tenant_by_phone_number_id(): void
    {
        $this->seed(DatabaseSeeder::class);

        $defaultCompany = Company::query()->where('slug', 'default')->firstOrFail();
        $defaultBranch = Branch::query()->where('company_id', $defaultCompany->id)->where('code', 'principal')->firstOrFail();
        Contact::create([
            'company_id' => $defaultCompany->id,
            'branch_id' => $defaultBranch->id,
            'first_name' => 'Default',
            'full_name' => 'Default Contact',
            'primary_phone' => '573001112233',
            'status' => 'active',
        ]);

        $other = $this->createCompanyWithBranch('webhook-canal', 'Webhook canal', 'principal');
        $channel = Channel::create([
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'name' => 'WhatsApp Webhook',
            'slug' => 'whatsapp',
            'is_active' => true,
            'settings' => ['phone_number_id' => 'phone-other-123'],
        ]);

        app(ProcessWhatsAppWebhookUseCase::class)->execute([
            'entry' => [[
                'id' => 'entry-phone-other',
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => 'phone-other-123'],
                        'messages' => [[
                            'id' => 'wamid-other-tenant',
                            'from' => '573001112233',
                            'text' => ['body' => 'Hola otra empresa'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $contact = Contact::query()
            ->where('company_id', $other['company']->id)
            ->where('primary_phone', '573001112233')
            ->firstOrFail();

        $this->assertDatabaseHas('inbound_messages', [
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'provider_message_id' => 'wamid-other-tenant',
        ]);
        $this->assertDatabaseHas('conversations', [
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
        ]);
        $this->assertDatabaseHas('provider_logs', [
            'company_id' => $other['company']->id,
            'branch_id' => $other['branch']->id,
            'external_event_id' => 'entry-phone-other',
        ]);
    }

    public function test_whatsapp_webhook_with_unknown_phone_number_id_does_not_create_operational_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        app(ProcessWhatsAppWebhookUseCase::class)->execute([
            'entry' => [[
                'id' => 'entry-unknown-phone-id',
                'changes' => [[
                    'value' => [
                        'metadata' => ['phone_number_id' => 'unknown-phone-id'],
                        'messages' => [[
                            'id' => 'wamid-unknown-phone-id',
                            'from' => '573007778888',
                            'text' => ['body' => 'No debe asociarse'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('provider_logs', ['external_event_id' => 'entry-unknown-phone-id']);
        $this->assertDatabaseMissing('contacts', ['primary_phone' => '573007778888']);
        $this->assertDatabaseMissing('inbound_messages', ['provider_message_id' => 'wamid-unknown-phone-id']);
    }

    public function test_whatsapp_provider_uses_credentials_from_message_channel(): void
    {
        $this->seed(DatabaseSeeder::class);

        config([
            'services.whatsapp.api_base_url' => 'https://graph.facebook.test/v21.0',
            'services.whatsapp.access_token' => 'env-token',
            'services.whatsapp.phone_number_id' => 'env-phone',
        ]);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();
        $channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();
        $channel->update(['settings' => [
            'api_base_url' => 'https://graph.facebook.test/v21.0',
            'access_token' => 'channel-token',
            'phone_number_id' => 'channel-phone',
        ]]);
        $contact = Contact::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'first_name' => 'Canal',
            'full_name' => 'Canal Credenciales',
            'primary_phone' => '573009990000',
            'status' => 'active',
        ]);
        $message = Message::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'contact_id' => $contact->id,
            'channel_id' => $channel->id,
            'type' => 'text',
            'status' => 'queued',
            'body' => 'Hola',
        ]);

        Http::fake([
            'graph.facebook.test/v21.0/channel-phone/messages' => Http::response(['messages' => [['id' => 'wamid-channel']]]),
        ]);

        $response = app(WhatsAppCloudApiProvider::class)->sendText(new SendTextMessageDTO($message->id, $contact->primary_phone, 'Hola'));

        $this->assertSame('wamid-channel', data_get($response, 'messages.0.id'));
        Http::assertSent(fn ($request): bool => $request->url() === 'https://graph.facebook.test/v21.0/channel-phone/messages'
            && $request->hasHeader('Authorization', 'Bearer channel-token'));
    }

    public function test_admin_can_update_active_company_and_create_branch_from_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $pro = Plan::query()->where('code', 'pro')->firstOrFail();

        CompanySubscription::query()->where('company_id', $company->id)->update([
            'plan_id' => $pro->id,
            'status' => 'active',
        ]);

        $this->actingAs($admin)
            ->get(route('tenancy.index'))
            ->assertOk()
            ->assertSee('Empresa, sedes y membresias');

        $this->actingAs($admin)
            ->patch(route('tenancy.company.update'), [
                'name' => 'Noia Default Actualizada',
                'legal_name' => 'Noia SAS',
                'tax_id' => '900123456',
                'timezone' => 'America/Bogota',
                'status' => 'active',
            ])
            ->assertRedirect(route('tenancy.index'));

        $this->actingAs($admin)
            ->post(route('tenancy.branches.store'), [
                'name' => 'Sede norte admin',
                'code' => 'norte-admin',
                'city' => 'Bogota',
                'address' => 'Calle 1',
                'timezone' => 'America/Bogota',
                'is_active' => '1',
            ])
            ->assertRedirect(route('tenancy.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Noia Default Actualizada',
            'tax_id' => '900123456',
        ]);
        $this->assertDatabaseHas('branches', [
            'company_id' => $company->id,
            'name' => 'Sede norte admin',
            'code' => 'norte-admin',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'module' => 'tenancy',
            'target_type' => Company::class,
            'target_id' => $company->id,
        ]);
    }

    public function test_admin_can_assign_user_membership_to_branch_from_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede membresias',
            'code' => 'membresias',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $operator = User::factory()->create(['email' => 'tenant-membership@noiachat.local']);
        $role = Role::query()->where('name', 'operator')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('tenancy.memberships.store'), [
                'user_id' => $operator->id,
                'branch_id' => $branch->id,
                'role_id' => $role->id,
                'is_default' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('tenancy.index'));

        $this->assertDatabaseHas('memberships', [
            'user_id' => $operator->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->assertTrue($operator->fresh()->roles()->where('name', 'operator')->exists());
    }

    public function test_tenancy_panel_blocks_updates_for_branch_from_another_company(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $other = $this->createCompanyWithBranch('tenancy-ajena', 'Tenancy ajena', 'principal');

        $this->actingAs($admin)
            ->patch(route('tenancy.branches.update', $other['branch']), [
                'name' => 'No deberia cambiar',
                'code' => 'ajena-editada',
                'city' => 'Medellin',
                'address' => 'Calle externa',
                'timezone' => 'America/Bogota',
                'is_active' => '1',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('branches', [
            'id' => $other['branch']->id,
            'code' => 'ajena-editada',
        ]);
    }

    public function test_company_admin_membership_can_administer_active_tenant_without_legacy_admin_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $companyAdminRole = Role::query()->where('name', 'company_admin')->firstOrFail();
        $companyAdmin = User::factory()->create(['email' => 'company-admin-role@noiachat.local']);

        Membership::create([
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $companyAdminRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($companyAdmin)
            ->get(route('tenancy.index'))
            ->assertOk()
            ->assertSee('Empresa, sedes y membresias');

        $this->actingAs($companyAdmin)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_super_admin_can_create_and_update_companies_globally(): void
    {
        $this->seed(DatabaseSeeder::class);

        $role = Role::query()->where('name', 'super_admin')->firstOrFail();
        $superAdmin = User::factory()->create(['email' => 'super-admin@noiachat.local']);
        $superAdmin->roles()->attach($role->id);

        $this->actingAs($superAdmin)
            ->post(route('tenancy.companies.store'), [
                'name' => 'Empresa global',
                'slug' => 'empresa-global',
                'legal_name' => 'Empresa Global SAS',
                'tax_id' => '901000111',
                'timezone' => 'America/Bogota',
                'status' => 'active',
                'default_branch_name' => 'Principal global',
                'default_branch_code' => 'principal',
            ])
            ->assertRedirect(route('tenancy.index'));

        $company = Company::query()->where('slug', 'empresa-global')->firstOrFail();

        $this->assertDatabaseHas('branches', [
            'company_id' => $company->id,
            'name' => 'Principal global',
            'code' => 'principal',
        ]);

        $this->actingAs($superAdmin)
            ->patch(route('tenancy.companies.update', $company), [
                'name' => 'Empresa global actualizada',
                'slug' => 'empresa-global',
                'legal_name' => 'Empresa Global SAS',
                'tax_id' => '901000222',
                'timezone' => 'America/Bogota',
                'status' => 'inactive',
            ])
            ->assertRedirect(route('tenancy.index'));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'Empresa global actualizada',
            'tax_id' => '901000222',
            'status' => 'inactive',
        ]);
    }

    public function test_company_admin_cannot_manage_companies_globally(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $role = Role::query()->where('name', 'company_admin')->firstOrFail();
        $companyAdmin = User::factory()->create(['email' => 'company-admin-no-global@noiachat.local']);

        Membership::create([
            'user_id' => $companyAdmin->id,
            'company_id' => $company->id,
            'branch_id' => null,
            'role_id' => $role->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($companyAdmin)
            ->post(route('tenancy.companies.store'), [
                'name' => 'Empresa bloqueada',
                'slug' => 'empresa-bloqueada',
                'timezone' => 'America/Bogota',
                'status' => 'active',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('companies', ['slug' => 'empresa-bloqueada']);
    }

    public function test_branch_manager_membership_can_view_branch_operations_but_not_administer_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();
        $branchManagerRole = Role::query()->where('name', 'branch_manager')->firstOrFail();
        $branchManager = User::factory()->create(['email' => 'branch-manager-role@noiachat.local']);

        Membership::create([
            'user_id' => $branchManager->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $branchManagerRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($branchManager)
            ->get(route('contacts.index'))
            ->assertOk();

        $this->actingAs($branchManager)
            ->get(route('audit-logs.index'))
            ->assertOk();

        $this->actingAs($branchManager)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_auditor_membership_cannot_send_or_manage_contacts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $company = Company::query()->where('slug', 'default')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->where('code', 'principal')->firstOrFail();
        $auditorRole = Role::query()->where('name', 'auditor')->firstOrFail();
        $auditor = User::factory()->create(['email' => 'auditor-role@noiachat.local']);

        Membership::create([
            'user_id' => $auditor->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'role_id' => $auditorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($auditor)
            ->get(route('contacts.index'))
            ->assertOk();

        $this->actingAs($auditor)
            ->get(route('contacts.create'))
            ->assertForbidden();
    }

    private function createCompanyWithBranch(string $slug, string $name, string $branchCode): array
    {
        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => $name.' principal',
            'code' => $branchCode,
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);

        return ['company' => $company, 'branch' => $branch];
    }
}
