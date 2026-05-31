<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
use App\Modules\Compliance\Domain\Enums\EligibilityStatus;
use App\Modules\Consents\Application\UseCases\GrantConsentUseCase;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Application\Services\ContactService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Application\Services\MessageStatusService;
use App\Modules\Messaging\Application\UseCases\QueueTemplateMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTextMessageUseCase;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Webhooks\Application\UseCases\ProcessWhatsAppWebhookUseCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NoiaChatMvpTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('email', env('NOIACHAT_ADMIN_EMAIL', env('AUDITCHAT_ADMIN_EMAIL', 'admin@noiachat.local')))->firstOrFail();
        $this->channel = Channel::where('slug', 'whatsapp')->firstOrFail();
    }

    public function test_cannot_send_message_without_consent(): void
    {
        $contact = $this->makeContact('573101000001');
        $this->assertSame(EligibilityStatus::BLOCKED_NO_CONSENT, app(ComplianceDecisionService::class)->decide($contact, $this->channel->id));
    }

    public function test_cannot_send_message_to_blacklisted_contact(): void
    {
        $contact = $this->makeContact('573101000002', true);
        ContactBlacklist::create(['contact_id' => $contact->id, 'channel_id' => $this->channel->id, 'reason' => 'manual', 'created_at' => now()]);
        $this->assertSame(EligibilityStatus::BLOCKED_BLACKLIST, app(ComplianceDecisionService::class)->decide($contact, $this->channel->id));
    }

    public function test_can_queue_message_for_active_contact_with_consent(): void
    {
        $contact = $this->makeContact('573101000003', true);
        $message = app(QueueTextMessageUseCase::class)->execute($contact, $this->channel->id, 'Hola', $this->admin->id);
        $this->assertSame('queued', $message->status);
    }

    public function test_sent_message_creates_message_event(): void
    {
        $contact = $this->makeContact('573101000004', true);
        $message = app(QueueTextMessageUseCase::class)->execute($contact, $this->channel->id, 'Hola', $this->admin->id);
        app(MessageStatusService::class)->transition($message, MessageStatus::SENT, ['mock' => true], 'unit_test');
        $this->assertDatabaseHas('message_events', ['message_id' => $message->id, 'status' => 'sent']);
    }

    public function test_delivered_webhook_updates_message_status(): void
    {
        $contact = $this->makeContact('573101000005', true);
        $message = Message::create(['contact_id' => $contact->id, 'channel_id' => $this->channel->id, 'user_id' => $this->admin->id, 'type' => 'text', 'status' => 'sent', 'provider_message_id' => 'wamid-123']);
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-delivered', 'changes' => [['value' => ['statuses' => [['id' => 'wamid-123', 'status' => 'delivered']]]]]]]]);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'delivered']);
    }

    public function test_inbound_stop_creates_opt_out_request(): void
    {
        $contact = $this->makeContact('573101000006', true);
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-stop', 'changes' => [['value' => ['messages' => [['id' => 'wamid-stop-1', 'from' => $contact->primary_phone, 'text' => ['body' => 'STOP']]]]]]]]]);
        $this->assertDatabaseCount('opt_out_requests', 1);
    }

    public function test_opt_out_adds_contact_to_blacklist(): void
    {
        $contact = $this->makeContact('573101000007', true);
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-blacklist', 'changes' => [['value' => ['messages' => [['id' => 'wamid-stop-2', 'from' => $contact->primary_phone, 'text' => ['body' => 'NO ENVIAR']]]]]]]]]);
        $this->assertDatabaseHas('contact_blacklist', ['contact_id' => $contact->id, 'channel_id' => $this->channel->id]);
    }

    public function test_auditor_cannot_send_messages(): void
    {
        $auditor = User::factory()->create();
        $auditor->roles()->attach(Role::where('name', 'auditor')->firstOrFail()->id);
        $contact = Contact::firstOrFail();
        $this->actingAs($auditor)->post(route('messages.send-text'), ['contact_id' => $contact->id, 'channel_id' => $this->channel->id, 'body' => 'Hola'])->assertForbidden();
    }

    public function test_operator_can_add_contact_to_blacklist_from_panel(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000008', true);

        $this->actingAs($operator)->post(route('contacts.blacklist.store', $contact), [
            'channel_id' => $this->channel->id,
            'reason' => 'manual_opt_out',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_blacklist', [
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'reason' => 'manual_opt_out',
        ]);
    }

    public function test_operator_can_assign_conversation(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000009', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)->put(route('conversations.assign', $conversation), [
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
        ])->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_create_template_from_settings(): void
    {
        $this->actingAs($this->admin)->post(route('settings.templates.store'), [
            'channel_id' => $this->channel->id,
            'name' => 'bienvenida_mvp',
            'external_template_id' => 'bienvenida_mvp',
            'language' => 'es',
            'body' => 'Hola {{1}}, bienvenido.',
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('message_templates', [
            'name' => 'bienvenida_mvp',
            'channel_id' => $this->channel->id,
        ]);
    }

    public function test_operator_can_reply_to_conversation(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000010', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)->post(route('conversations.reply', $conversation), [
            'body' => 'Respuesta de seguimiento',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'body' => 'Respuesta de seguimiento',
        ]);
    }

    public function test_admin_can_toggle_template_status(): void
    {
        $template = \App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate::firstOrFail();

        $this->actingAs($this->admin)->patch(route('settings.templates.toggle', $template))
            ->assertRedirect();

        $this->assertDatabaseHas('message_templates', [
            'id' => $template->id,
            'is_active' => 0,
        ]);
    }

    public function test_operator_can_reply_to_conversation_with_document(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000011', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)->post(route('conversations.reply-media', $conversation), [
            'type' => 'document',
            'body' => 'Adjunto solicitado',
            'file' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $message = Message::query()->where('conversation_id', $conversation->id)->latest()->firstOrFail();

        $this->assertSame('document', $message->type);
        $this->assertDatabaseHas('message_attachments', ['message_id' => $message->id]);
    }

    public function test_operator_can_reply_to_conversation_with_template(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000012', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $template = MessageTemplate::query()->with('currentVersion')->firstOrFail();

        $this->actingAs($operator)->post(route('conversations.reply-template', $conversation), [
            'message_template_id' => $template->id,
            'variables' => 'Carlos|9981',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'type' => 'template',
            'message_template_id' => $template->id,
        ]);
    }

    public function test_operator_can_retry_failed_message(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000013', true);
        $message = Message::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'user_id' => $operator->id,
            'type' => 'text',
            'status' => 'failed',
            'body' => 'Reintentar',
            'retry_count' => 0,
        ]);

        $this->actingAs($operator)->post(route('messages.retry', $message))
            ->assertRedirect(route('messages.show', $message));

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'status' => 'queued',
            'retry_count' => 1,
        ]);
        $this->assertDatabaseHas('message_events', [
            'message_id' => $message->id,
            'event_type' => 'retry_requested',
        ]);
    }

    private function makeContact(string $phone, bool $consent = false): Contact
    {
        $contact = app(ContactService::class)->create(new UpsertContactDTO('Test', 'User', null, $phone), $this->admin->id);
        if ($consent) {
            app(GrantConsentUseCase::class)->execute($contact, $this->channel->id, 'manual', $this->admin->id);
        }
        return $contact;
    }
}
