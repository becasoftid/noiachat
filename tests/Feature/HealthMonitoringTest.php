<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class HealthMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
    }

    public function test_admin_can_view_health_monitoring_panel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('health.index'))
            ->assertOk()
            ->assertSee('Salud operativa')
            ->assertSee('Jobs fallidos')
            ->assertSee('Cola pendiente')
            ->assertSee('Disco storage')
            ->assertSee('Rotacion token WhatsApp')
            ->assertSee('Webhook WhatsApp')
            ->assertSee('php artisan noiachat:health-check');
    }

    public function test_operator_cannot_view_health_monitoring_panel(): void
    {
        $operator = User::factory()->create([
            'email' => 'operador-health@example.test',
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

        $this->actingAs($operator)
            ->get(route('health.index'))
            ->assertForbidden();
    }

    public function test_health_check_command_fails_when_failed_jobs_exist(): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\HealthFailingJob']),
            'exception' => 'RuntimeException: fallo',
            'failed_at' => now(),
        ]);

        $this->artisan('noiachat:health-check')
            ->expectsOutputToContain('Estado general: critical')
            ->assertFailed();
    }

    public function test_health_check_command_fails_when_whatsapp_token_is_expired(): void
    {
        \App\Modules\Contacts\Infrastructure\Persistence\Models\Channel::query()
            ->where('slug', 'whatsapp')
            ->firstOrFail()
            ->update([
                'settings' => [
                    'provider' => 'whatsapp_cloud',
                    'access_token' => 'secret-access-token-value-123456',
                    'access_token_expires_at' => now()->subDay()->format('Y-m-d'),
                    'access_token_responsible' => 'Equipo Operaciones',
                    'access_token_rotation_procedure' => 'Rotar en Meta y actualizar canal.',
                ],
            ]);

        $this->artisan('noiachat:health-check')
            ->expectsOutputToContain('Estado general: critical')
            ->assertFailed();
    }
}
