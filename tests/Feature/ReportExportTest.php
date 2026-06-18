<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
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

    public function test_audit_export_downloads_filtered_csv_for_active_tenant(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => 'Contact',
            'target_id' => 'contact-visible',
            'old_values_json' => ['full_name' => 'Anterior'],
            'new_values_json' => ['full_name' => 'Visible CSV'],
        ]);
        $this->createOtherTenantAuditLog();

        $response = $this->actingAs($this->admin)
            ->get(route('reports.exports.audit-logs', ['module' => 'contacts']));

        $response->assertOk();
        $response->assertHeader('content-disposition', 'attachment; filename=auditoria.csv');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Visible CSV', $content);
        $this->assertStringNotContainsString('Otra empresa CSV', $content);
    }

    public function test_contact_message_and_conversation_exports_download_csv(): void
    {
        $contact = Contact::create([
            'first_name' => 'Exportable',
            'last_name' => 'Uno',
            'full_name' => 'Exportable Uno',
            'email' => 'exportable@example.test',
            'primary_phone' => '573001110010',
            'status' => 'active',
        ]);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $this->admin->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        Message::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $conversation->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'sent',
            'body' => 'Mensaje exportable',
        ]);

        $contacts = $this->actingAs($this->admin)->get(route('reports.exports.contacts', ['search' => 'Exportable']));
        $contacts->assertOk();
        $this->assertStringContainsString('Exportable Uno', $contacts->streamedContent());

        $messages = $this->actingAs($this->admin)->get(route('reports.exports.messages', ['search' => 'Exportable']));
        $messages->assertOk();
        $this->assertStringContainsString('sent', $messages->streamedContent());

        $conversations = $this->actingAs($this->admin)->get(route('reports.exports.conversations', ['status' => 'open']));
        $conversations->assertOk();
        $this->assertStringContainsString('Exportable Uno', $conversations->streamedContent());
    }

    public function test_operator_cannot_export_audit_logs(): void
    {
        $operator = User::factory()->create([
            'email' => 'operador-export@example.test',
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
            ->get(route('reports.exports.audit-logs'))
            ->assertForbidden();
    }

    private function createOtherTenantAuditLog(): void
    {
        $company = Company::create([
            'name' => 'Empresa export externa',
            'slug' => 'empresa-export-externa',
            'status' => 'active',
            'timezone' => 'America/Bogota',
        ]);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'Sede export externa',
            'code' => 'export-externa',
            'timezone' => 'America/Bogota',
            'is_active' => true,
        ]);

        AuditLog::create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => 'Contact',
            'target_id' => 'contact-hidden',
            'old_values_json' => ['full_name' => 'Otra empresa antes'],
            'new_values_json' => ['full_name' => 'Otra empresa CSV'],
        ]);
    }
}
