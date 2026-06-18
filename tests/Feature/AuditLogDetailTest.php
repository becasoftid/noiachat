<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
    }

    public function test_admin_can_view_audit_log_old_new_detail(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => 'Contact',
            'target_id' => 'contact-123',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Feature Test',
            'old_values_json' => [
                'full_name' => 'Cliente Antiguo',
                'status' => 'inactive',
            ],
            'new_values_json' => [
                'full_name' => 'Cliente Nuevo',
                'status' => 'active',
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.show', $log))
            ->assertOk()
            ->assertSee('Detalle de auditoría')
            ->assertSee('Cambios old/new')
            ->assertSee('full_name')
            ->assertSee('Cliente Antiguo')
            ->assertSee('Cliente Nuevo')
            ->assertSee('Feature Test');
    }

    public function test_operator_cannot_view_audit_log_detail(): void
    {
        $operator = User::factory()->create([
            'email' => 'operador-auditoria@example.test',
            'is_active' => true,
        ]);
        $operatorRole = Role::query()->where('name', 'operator')->firstOrFail();
        $operator->roles()->attach($operatorRole);
        $defaultMembership = $this->admin->memberships()->firstOrFail();
        Membership::create([
            'user_id' => $operator->id,
            'company_id' => $defaultMembership->company_id,
            'branch_id' => $defaultMembership->branch_id,
            'role_id' => $operatorRole->id,
            'is_default' => true,
            'is_active' => true,
        ]);
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => 'Contact',
            'target_id' => 'contact-456',
            'old_values_json' => ['status' => 'inactive'],
            'new_values_json' => ['status' => 'active'],
        ]);

        $this->actingAs($operator)
            ->get(route('audit-logs.show', $log))
            ->assertForbidden();
    }

    public function test_audit_log_detail_respects_active_tenant(): void
    {
        $otherCompany = Company::create([
            'name' => 'Empresa auditoria externa',
            'slug' => 'empresa-auditoria-externa',
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sede auditoria externa',
            'code' => 'auditoria-externa',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $log = AuditLog::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => 'Contact',
            'target_id' => 'contact-789',
            'old_values_json' => ['full_name' => 'Otra empresa antes'],
            'new_values_json' => ['full_name' => 'Otra empresa despues'],
        ]);

        $this->actingAs($this->admin)
            ->get(route('audit-logs.show', $log))
            ->assertForbidden();
    }
}
