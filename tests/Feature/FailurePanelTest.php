<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class FailurePanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::query()->where('email', 'admin@noiachat.local')->firstOrFail();
        $this->channel = Channel::query()->where('slug', 'whatsapp')->firstOrFail();
    }

    public function test_admin_can_view_recent_failures_panel(): void
    {
        $contact = Contact::create([
            'first_name' => 'Cliente',
            'last_name' => 'Fallido',
            'full_name' => 'Cliente Fallido',
            'email' => 'fallido@example.test',
            'primary_phone' => '573001110001',
            'status' => 'active',
        ]);
        $message = Message::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'failed',
            'body' => 'Hola',
            'failed_at' => now(),
        ]);
        $message->providerLogs()->create([
            ...$message->tenantAttributes(),
            'provider' => 'whatsapp_cloud',
            'direction' => 'outbound',
            'event_type' => 'send_text_failed',
            'payload' => [
                'error' => [
                    'code' => 131000,
                    'message' => 'Meta rechazo el mensaje',
                    'error_data' => ['details' => 'Telefono invalido'],
                ],
            ],
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'App\\Jobs\\DemoJob']),
            'exception' => "RuntimeException: Fallo de prueba\nStack trace",
            'failed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('failures.index'))
            ->assertOk()
            ->assertSee('Fallos recientes')
            ->assertSee('Cliente Fallido')
            ->assertSee('Meta rechazo el mensaje')
            ->assertSee('Telefono invalido')
            ->assertSee('App\\Jobs\\DemoJob');
    }

    public function test_operator_cannot_view_recent_failures_panel(): void
    {
        $operator = User::factory()->create([
            'email' => 'operador-fallos@example.test',
            'is_active' => true,
        ]);
        $operator->roles()->attach(Role::query()->where('name', 'operator')->firstOrFail());
        $defaultMembership = $this->admin->memberships()->firstOrFail();
        Membership::create([
            'user_id' => $operator->id,
            'company_id' => $defaultMembership->company_id,
            'branch_id' => $defaultMembership->branch_id,
            'role_id' => Role::query()->where('name', 'operator')->firstOrFail()->id,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($operator)
            ->get(route('failures.index'))
            ->assertForbidden();
    }

    public function test_failure_panel_respects_active_tenant_for_messages_and_provider_logs(): void
    {
        $defaultContact = Contact::create([
            'first_name' => 'Empresa',
            'last_name' => 'Actual',
            'full_name' => 'Empresa Actual',
            'email' => 'actual@example.test',
            'primary_phone' => '573001110002',
            'status' => 'active',
        ]);
        Message::create([
            'contact_id' => $defaultContact->id,
            'channel_id' => $this->channel->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'failed',
            'body' => 'Visible',
            'failed_at' => now(),
        ]);

        $otherCompany = Company::create([
            'name' => 'Empresa externa',
            'slug' => 'empresa-externa',
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $otherBranch = Branch::create([
            'company_id' => $otherCompany->id,
            'name' => 'Sede externa',
            'code' => 'externa',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);
        $otherContact = Contact::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'first_name' => 'Empresa',
            'last_name' => 'Externa',
            'full_name' => 'Empresa Externa',
            'email' => 'externa@example.test',
            'primary_phone' => '573001110003',
            'status' => 'active',
        ]);
        $otherMessage = Message::create([
            'company_id' => $otherCompany->id,
            'branch_id' => $otherBranch->id,
            'contact_id' => $otherContact->id,
            'channel_id' => $this->channel->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'failed',
            'body' => 'No visible',
            'failed_at' => now(),
        ]);
        $otherMessage->providerLogs()->create([
            ...$otherMessage->tenantAttributes(),
            'provider' => 'whatsapp_cloud',
            'direction' => 'outbound',
            'event_type' => 'send_text_failed',
            'payload' => ['error' => ['message' => 'Error de otra empresa']],
        ]);

        $this->actingAs($this->admin)
            ->get(route('failures.index'))
            ->assertOk()
            ->assertSee('Empresa Actual')
            ->assertDontSee('Empresa Externa')
            ->assertDontSee('Error de otra empresa');
    }
}
