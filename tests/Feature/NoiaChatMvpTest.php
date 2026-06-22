<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Compliance\Application\Services\ComplianceDecisionService;
use App\Modules\Compliance\Domain\Enums\EligibilityStatus;
use App\Modules\Consents\Application\UseCases\GrantConsentUseCase;
use App\Modules\Consents\Application\UseCases\RevokeConsentUseCase;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Application\Services\ContactService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Messaging\Application\Services\MessageStatusService;
use App\Modules\Messaging\Application\DTOs\SendDocumentMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendImageMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTemplateMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTextMessageDTO;
use App\Modules\Messaging\Application\DTOs\UploadMediaDTO;
use App\Modules\Messaging\Application\UseCases\QueueMediaMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTemplateMessageUseCase;
use App\Modules\Messaging\Application\UseCases\QueueTextMessageUseCase;
use App\Modules\Messaging\Domain\Enums\MessageStatus;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppDocumentJob;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppImageJob;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\MessageTemplate;
use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;
use App\Modules\Messaging\Infrastructure\Jobs\SendWhatsAppTextJob;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Shared\Domain\Contracts\MessagingProviderInterface;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Webhooks\Application\UseCases\ProcessWhatsAppWebhookUseCase;
use App\Modules\Webhooks\Infrastructure\Jobs\ProcessWhatsAppWebhookJob;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
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
        $this->openCustomerCareWindow($contact);
        $message = app(QueueTextMessageUseCase::class)->execute($contact, $this->channel->id, 'Hola', $this->admin->id);
        $this->assertSame('queued', $message->status);
    }

    public function test_cannot_send_free_form_message_outside_whatsapp_customer_care_window(): void
    {
        $contact = $this->makeContact('573101000016', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));

        $message = app(QueueTextMessageUseCase::class)->execute($contact, $this->channel->id, 'Hola', $this->admin->id);

        $this->assertSame('blocked_by_policy', $message->status);
        $this->assertSame('blocked_customer_care_window', data_get($message->meta, 'eligibility_status'));
    }

    public function test_sent_message_creates_message_event(): void
    {
        $contact = $this->makeContact('573101000004', true);
        $this->openCustomerCareWindow($contact);
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

    public function test_read_webhook_updates_message_status_and_timestamp(): void
    {
        $contact = $this->makeContact('573101000105', true);
        $message = Message::create(['contact_id' => $contact->id, 'channel_id' => $this->channel->id, 'user_id' => $this->admin->id, 'type' => 'text', 'status' => 'delivered', 'provider_message_id' => 'wamid-read-123']);

        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-read', 'changes' => [['value' => ['statuses' => [['id' => 'wamid-read-123', 'status' => 'read']]]]]]]]);

        $message->refresh();

        $this->assertSame('read', $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_conversation_panel_shows_read_indicator_for_outbound_messages(): void
    {
        $contact = $this->makeContact('573101000106', true);
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
            'status' => 'read',
            'body' => 'Mensaje leido por el contacto',
            'provider_message_id' => 'wamid-panel-read',
            'read_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('conversations.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Leído')
            ->assertSee('&check;&check;', false);
    }

    public function test_messages_new_send_button_opens_conversation_new_chat_flow(): void
    {
        $this->actingAs($this->admin)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee(route('conversations.index', ['new' => 1]), false)
            ->assertSee('Nuevo envío');
    }

    public function test_operator_can_start_new_chat_from_conversations(): void
    {
        $contact = $this->makeContact('573101000107', true);

        $response = $this->actingAs($this->admin)->post(route('conversations.start'), [
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
        ]);

        $conversation = Conversation::query()
            ->where('contact_id', $contact->id)
            ->where('channel_id', $this->channel->id)
            ->firstOrFail();

        $response
            ->assertRedirect(route('conversations.index', ['conversation' => $conversation->id]))
            ->assertSessionHas('status', 'Conversacion lista para operar.');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
        ]);
    }

    public function test_start_new_chat_reuses_existing_operational_conversation(): void
    {
        $contact = $this->makeContact('573101000108', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $this->admin->id,
            'status' => 'pending',
            'last_message_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($this->admin)->post(route('conversations.start'), [
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
        ])->assertRedirect(route('conversations.index', ['conversation' => $conversation->id]));

        $this->assertSame(1, Conversation::query()
            ->where('contact_id', $contact->id)
            ->where('channel_id', $this->channel->id)
            ->count());
    }

    public function test_whatsapp_webhook_accepts_valid_meta_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'test-meta-secret']);
        $json = json_encode(['entry' => [['id' => 'entry-signature-ok']]]) ?: '{}';
        $signature = 'sha256='.hash_hmac('sha256', $json, 'test-meta-secret');

        $this->call('POST', route('webhooks.whatsapp.receive'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $json)->assertOk();

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    }

    public function test_whatsapp_webhook_rejects_invalid_meta_signature(): void
    {
        config(['services.whatsapp.app_secret' => 'test-meta-secret']);
        $json = json_encode(['entry' => [['id' => 'entry-signature-bad']]]) ?: '{}';

        $this->call('POST', route('webhooks.whatsapp.receive'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], $json)->assertForbidden();

        Queue::assertNotPushed(ProcessWhatsAppWebhookJob::class);
    }

    public function test_inbound_stop_creates_opt_out_request(): void
    {
        $contact = $this->makeContact('573101000006', true);
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-stop', 'changes' => [['value' => ['messages' => [['id' => 'wamid-stop-1', 'from' => $contact->primary_phone, 'text' => ['body' => 'STOP']]]]]]]]]);
        $this->assertDatabaseCount('opt_out_requests', 1);
    }

    public function test_inbound_message_from_unknown_phone_creates_contact_and_conversation(): void
    {
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-new-contact', 'changes' => [['value' => ['messages' => [['id' => 'wamid-inbound-new', 'from' => '573028018618', 'text' => ['body' => 'Prueba noia']]]]]]]]]);

        $contact = Contact::query()->where('primary_phone', '573028018618')->firstOrFail();
        $conversation = Conversation::query()->where('contact_id', $contact->id)->firstOrFail();

        $this->assertDatabaseHas('inbound_messages', [
            'provider_message_id' => 'wamid-inbound-new',
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'body' => 'Prueba noia',
        ]);
    }

    public function test_inbound_message_matches_existing_contact_with_local_phone_format(): void
    {
        $contact = $this->makeContact('3028018618', true);

        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-existing-contact', 'changes' => [['value' => ['messages' => [['id' => 'wamid-inbound-existing', 'from' => '573028018618', 'text' => ['body' => 'Hola desde WhatsApp']]]]]]]]]);

        $conversation = Conversation::query()->where('contact_id', $contact->id)->firstOrFail();

        $this->assertDatabaseHas('inbound_messages', [
            'provider_message_id' => 'wamid-inbound-existing',
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'body' => 'Hola desde WhatsApp',
        ]);
    }

    public function test_opt_out_adds_contact_to_blacklist(): void
    {
        $contact = $this->makeContact('573101000007', true);
        app(ProcessWhatsAppWebhookUseCase::class)->execute(['entry' => [['id' => 'entry-blacklist', 'changes' => [['value' => ['messages' => [['id' => 'wamid-stop-2', 'from' => $contact->primary_phone, 'text' => ['body' => 'NO ENVIAR']]]]]]]]]);
        $this->assertDatabaseHas('contact_blacklist', ['contact_id' => $contact->id, 'channel_id' => $this->channel->id]);
    }

    public function test_contact_detail_shows_consent_history(): void
    {
        $contact = $this->makeContact('573101000027');
        app(GrantConsentUseCase::class)->execute($contact, $this->channel->id, 'whatsapp', $this->admin->id);
        app(RevokeConsentUseCase::class)->execute($contact, $this->channel->id, $this->admin->id);

        $this->actingAs($this->admin)->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertSee('Consentimientos')
            ->assertSee('WhatsApp')
            ->assertSee('Otorgado')
            ->assertSee('Revocado')
            ->assertSee('Fuente: WhatsApp')
            ->assertSee('Otorgado')
            ->assertSee('Revocado')
            ->assertSee($this->admin->name);
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

    public function test_operator_can_assign_conversation_to_self(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000017', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)->put(route('conversations.assign-me', $conversation))->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
        ]);
    }

    public function test_operator_can_filter_my_conversations(): void
    {
        $operator = User::factory()->create(['name' => 'Operador Propio']);
        $otherOperator = User::factory()->create(['name' => 'Operador Externo']);
        $roleId = Role::where('name', 'operator')->firstOrFail()->id;
        $operator->roles()->attach($roleId);
        $otherOperator->roles()->attach($roleId);

        $ownContact = $this->makeContact('573101000018', true);
        $otherContact = $this->makeContact('573101000019', true);

        Conversation::create([
            'contact_id' => $ownContact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
        ]);

        Conversation::create([
            'contact_id' => $otherContact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $otherOperator->id,
            'status' => 'pending',
            'last_message_at' => now()->subMinute(),
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee($ownContact->primary_phone)
            ->assertDontSee($otherContact->primary_phone);
    }

    public function test_conversation_inbox_shows_unread_inbound_messages(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000020', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
            'last_read_at' => now()->subMinutes(5),
        ]);

        InboundMessage::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $conversation->id,
            'provider_message_id' => 'wamid-unread-1',
            'from_phone' => $contact->primary_phone,
            'body' => 'Necesito ayuda',
            'payload' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.index', ['mine' => 1]))
            ->assertOk()
            ->assertSee('1 sin leer');
    }

    public function test_opening_conversation_marks_it_as_read(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000021', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
            'last_read_at' => null,
        ]);

        InboundMessage::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $conversation->id,
            'provider_message_id' => 'wamid-unread-2',
            'from_phone' => $contact->primary_phone,
            'body' => 'Hola',
            'payload' => [],
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.show', $conversation))
            ->assertOk();

        $this->assertNotNull($conversation->refresh()->last_read_at);

        $this->actingAs($operator)
            ->get(route('conversations.index', ['mine' => 1]))
            ->assertOk()
            ->assertDontSee('sin leer');
    }

    public function test_conversation_index_can_show_active_conversation_panel(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000024', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
            'last_read_at' => null,
        ]);

        InboundMessage::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $conversation->id,
            'provider_message_id' => 'wamid-active-panel-1',
            'from_phone' => $contact->primary_phone,
            'body' => 'Mensaje visible en panel unico',
            'payload' => [],
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Mensaje visible en panel unico')
            ->assertSee('Respuesta de texto');

        $this->assertNotNull($conversation->refresh()->last_read_at);
    }

    public function test_conversation_inbox_refresh_returns_filtered_partial(): void
    {
        $operator = User::factory()->create(['name' => 'Operador Refresh']);
        $otherOperator = User::factory()->create(['name' => 'Operador Oculto']);
        $roleId = Role::where('name', 'operator')->firstOrFail()->id;
        $operator->roles()->attach($roleId);
        $otherOperator->roles()->attach($roleId);

        $ownContact = $this->makeContact('573101000022', true);
        $otherContact = $this->makeContact('573101000023', true);

        $ownConversation = Conversation::create([
            'contact_id' => $ownContact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
            'last_read_at' => now()->subMinutes(10),
        ]);

        Conversation::create([
            'contact_id' => $otherContact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $otherOperator->id,
            'status' => 'pending',
            'last_message_at' => now()->subMinute(),
        ]);

        InboundMessage::create([
            'contact_id' => $ownContact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $ownConversation->id,
            'provider_message_id' => 'wamid-refresh-1',
            'from_phone' => $ownContact->primary_phone,
            'body' => 'Ping refresh',
            'payload' => [],
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.refresh', ['mine' => 1]))
            ->assertOk()
            ->assertSee($ownContact->primary_phone)
            ->assertSee('1 sin leer')
            ->assertDontSee($otherContact->primary_phone)
            ->assertDontSee('<html', false);
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

        $template = MessageTemplate::query()->where('name', 'bienvenida_mvp')->firstOrFail();
        $this->assertSame(1, $template->currentVersion->variable_count);
    }

    public function test_admin_can_sync_whatsapp_templates_from_meta(): void
    {
        config([
            'services.whatsapp.api_base_url' => 'https://graph.facebook.test/v21.0',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.business_account_id' => 'waba-123',
        ]);

        Http::fake([
            'graph.facebook.test/v21.0/waba-123/message_templates*' => Http::response([
                'data' => [[
                    'id' => 'meta-template-1',
                    'name' => 'recordatorio_pago_meta',
                    'language' => 'es',
                    'status' => 'APPROVED',
                    'category' => 'UTILITY',
                    'components' => [
                        ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Recordatorio'],
                        ['type' => 'BODY', 'text' => 'Hola {{1}}, tu referencia es {{2}}.'],
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.templates.sync-whatsapp'))
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message) => str_contains($message, '1 plantillas'));

        $this->assertDatabaseHas('message_templates', [
            'channel_id' => $this->channel->id,
            'name' => 'recordatorio_pago_meta',
            'external_template_id' => 'recordatorio_pago_meta',
            'meta_template_id' => 'meta-template-1',
            'meta_status' => 'APPROVED',
            'meta_category' => 'UTILITY',
            'is_active' => 1,
        ]);

        $template = MessageTemplate::query()->where('meta_template_id', 'meta-template-1')->firstOrFail();

        $this->assertSame('Hola {{1}}, tu referencia es {{2}}.', $template->currentVersion->body);
        $this->assertSame(2, $template->currentVersion->variable_count);
    }

    public function test_whatsapp_template_sync_reports_missing_credentials(): void
    {
        config([
            'services.whatsapp.access_token' => '',
            'services.whatsapp.business_account_id' => '',
        ]);

        $this->actingAs($this->admin)
            ->post(route('settings.templates.sync-whatsapp'))
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'WHATSAPP_BUSINESS_ACCOUNT_ID'));
    }

    public function test_operator_can_reply_to_conversation(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000010', true);
        $this->openCustomerCareWindow($contact);
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
        $this->openCustomerCareWindow($contact);
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
        Queue::assertPushed(SendWhatsAppDocumentJob::class);
    }

    public function test_media_message_without_consent_is_blocked_without_attachment_or_dispatch(): void
    {
        $contact = $this->makeContact('573101000015');

        $message = app(QueueMediaMessageUseCase::class)->execute(
            $contact,
            $this->channel->id,
            'document',
            UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
            'Adjunto solicitado',
            $this->admin->id,
        );

        $this->assertSame('document', $message->type);
        $this->assertSame('blocked_by_policy', $message->status);
        $this->assertDatabaseMissing('message_attachments', ['message_id' => $message->id]);
        Queue::assertNotPushed(SendWhatsAppDocumentJob::class);
    }

    public function test_whatsapp_image_job_uses_public_https_media_url(): void
    {
        config(['filesystems.disks.public.url' => 'https://noiachat.example/storage']);

        $contact = $this->makeContact('573101000029', true);
        $this->openCustomerCareWindow($contact);
        $message = app(QueueMediaMessageUseCase::class)->execute(
            $contact,
            $this->channel->id,
            'image',
            UploadedFile::fake()->create('evidencia.jpg', 100, 'image/jpeg'),
            'Foto publica',
            $this->admin->id,
        );

        $provider = new class implements MessagingProviderInterface {
            public ?SendImageMessageDTO $imageDto = null;

            public function sendText(SendTextMessageDTO $dto): array
            {
                return [];
            }

            public function sendImage(SendImageMessageDTO $dto): array
            {
                $this->imageDto = $dto;

                return ['messages' => [['id' => 'wamid-image-public']]];
            }

            public function sendDocument(SendDocumentMessageDTO $dto): array
            {
                return [];
            }

            public function sendTemplate(SendTemplateMessageDTO $dto): array
            {
                return [];
            }

            public function uploadMedia(UploadMediaDTO $dto): array
            {
                return [];
            }

            public function parseWebhook(array $payload): array
            {
                return $payload;
            }
        };

        (new SendWhatsAppImageJob($message->id))->handle($provider, app(MessageStatusService::class), app(\App\Modules\Media\Application\Services\PublicMediaUrlResolver::class));

        $this->assertNotNull($provider->imageDto);
        $this->assertStringStartsWith('https://noiachat.example/storage/messages/', $provider->imageDto->mediaUrl);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'sent', 'provider_message_id' => 'wamid-image-public']);
    }

    public function test_whatsapp_image_job_fails_when_media_url_is_not_public_https(): void
    {
        config(['filesystems.disks.public.url' => 'http://localhost/storage']);

        $contact = $this->makeContact('573101000030', true);
        $this->openCustomerCareWindow($contact);
        $message = app(QueueMediaMessageUseCase::class)->execute(
            $contact,
            $this->channel->id,
            'image',
            UploadedFile::fake()->create('evidencia.jpg', 100, 'image/jpeg'),
            'Foto local',
            $this->admin->id,
        );

        $provider = new class implements MessagingProviderInterface {
            public bool $imageCalled = false;

            public function sendText(SendTextMessageDTO $dto): array
            {
                return [];
            }

            public function sendImage(SendImageMessageDTO $dto): array
            {
                $this->imageCalled = true;

                return ['messages' => [['id' => 'wamid-image-local']]];
            }

            public function sendDocument(SendDocumentMessageDTO $dto): array
            {
                return [];
            }

            public function sendTemplate(SendTemplateMessageDTO $dto): array
            {
                return [];
            }

            public function uploadMedia(UploadMediaDTO $dto): array
            {
                return [];
            }

            public function parseWebhook(array $payload): array
            {
                return $payload;
            }
        };

        (new SendWhatsAppImageJob($message->id))->handle($provider, app(MessageStatusService::class), app(\App\Modules\Media\Application\Services\PublicMediaUrlResolver::class));

        $this->assertFalse($provider->imageCalled);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'failed']);
        $this->assertDatabaseHas('message_events', ['message_id' => $message->id, 'status' => 'failed', 'event_type' => 'media_url_invalid']);
    }

    public function test_whatsapp_document_job_uses_public_https_media_url(): void
    {
        config(['filesystems.disks.public.url' => 'https://noiachat.example/storage']);

        $contact = $this->makeContact('573101000031', true);
        $this->openCustomerCareWindow($contact);
        $message = app(QueueMediaMessageUseCase::class)->execute(
            $contact,
            $this->channel->id,
            'document',
            UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
            'Documento publico',
            $this->admin->id,
        );

        $provider = new class implements MessagingProviderInterface {
            public ?SendDocumentMessageDTO $documentDto = null;

            public function sendText(SendTextMessageDTO $dto): array
            {
                return [];
            }

            public function sendImage(SendImageMessageDTO $dto): array
            {
                return [];
            }

            public function sendDocument(SendDocumentMessageDTO $dto): array
            {
                $this->documentDto = $dto;

                return ['messages' => [['id' => 'wamid-document-public']]];
            }

            public function sendTemplate(SendTemplateMessageDTO $dto): array
            {
                return [];
            }

            public function uploadMedia(UploadMediaDTO $dto): array
            {
                return [];
            }

            public function parseWebhook(array $payload): array
            {
                return $payload;
            }
        };

        (new SendWhatsAppDocumentJob($message->id))->handle($provider, app(MessageStatusService::class), app(\App\Modules\Media\Application\Services\PublicMediaUrlResolver::class));

        $this->assertNotNull($provider->documentDto);
        $this->assertStringStartsWith('https://noiachat.example/storage/messages/', $provider->documentDto->mediaUrl);
        $this->assertSame('manual.pdf', $provider->documentDto->filename);
        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'sent', 'provider_message_id' => 'wamid-document-public']);
    }

    public function test_operator_can_reply_to_conversation_with_template(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000012', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $template = MessageTemplate::query()->with('currentVersion')->firstOrFail();

        $this->actingAs($operator)->post(route('conversations.reply-template', $conversation), [
            'message_template_id' => $template->id,
            'variables' => 'Carlos',
        ])->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'type' => 'template',
            'message_template_id' => $template->id,
        ]);
    }

    public function test_template_reply_requires_exact_variable_count(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000026', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $template = MessageTemplate::query()->with('currentVersion')->firstOrFail();

        $this->actingAs($operator)->from(route('conversations.show', $conversation))->post(route('conversations.reply-template', $conversation), [
            'message_template_id' => $template->id,
            'variables' => '',
        ])->assertRedirect(route('conversations.show', $conversation))
            ->assertSessionHasErrors('variables');

        $this->assertDatabaseMissing('messages', [
            'contact_id' => $contact->id,
            'conversation_id' => $conversation->id,
            'type' => 'template',
            'message_template_id' => $template->id,
        ]);
    }

    public function test_template_queue_use_case_rejects_incomplete_variables(): void
    {
        $contact = $this->makeContact('573101000027', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));
        $template = MessageTemplate::query()->with('currentVersion')->firstOrFail();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('Esta plantilla requiere 1 variables.');

        app(QueueTemplateMessageUseCase::class)->execute($contact, $template, $this->admin->id, []);
    }

    public function test_template_message_can_be_queued_outside_customer_care_window(): void
    {
        $contact = $this->makeContact('573101000017', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));
        $template = MessageTemplate::query()->with('currentVersion')->firstOrFail();

        $message = app(QueueTemplateMessageUseCase::class)->execute($contact, $template, $this->admin->id, ['Carlos']);

        $this->assertSame('queued', $message->status);
        $this->assertSame('template', $message->type);
        $this->assertSame('allowed', data_get($message->meta, 'eligibility_status'));
    }

    public function test_conversation_warns_when_customer_care_window_is_closed(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000028', true);
        $this->openCustomerCareWindow($contact, now()->subHours(25));
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Ventana 24h cerrada')
            ->assertSee('Usa una plantilla aprobada')
            ->assertSee('disabled', false);
    }

    public function test_blocked_message_redirect_shows_compliance_reason(): void
    {
        $contact = $this->makeContact('573101000024', false);

        $this->actingAs($this->admin)->post(route('messages.send-text'), [
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'body' => 'Hola sin consentimiento',
        ])->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'El contacto no tiene consentimiento vigente'));

        $message = Message::query()->where('contact_id', $contact->id)->latest()->firstOrFail();

        $this->assertSame('blocked_by_policy', $message->status);
    }

    public function test_message_detail_and_index_show_compliance_reason(): void
    {
        $contact = $this->makeContact('573101000025', false);
        $message = app(QueueTextMessageUseCase::class)->execute($contact, $this->channel->id, 'Hola', $this->admin->id);

        $this->actingAs($this->admin)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Envio bloqueado')
            ->assertSee('Sin consentimiento')
            ->assertSee('El contacto no tiene consentimiento vigente para este canal.');

        $this->actingAs($this->admin)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Sin consentimiento');
    }

    public function test_conversation_reply_shows_compliance_reason_when_blocked(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('name', 'operator')->firstOrFail()->id);
        $contact = $this->makeContact('573101000026', false);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'assigned_user_id' => $operator->id,
            'status' => 'pending',
            'last_message_at' => now(),
        ]);

        $this->actingAs($operator)->post(route('conversations.reply', $conversation), [
            'body' => 'Respuesta bloqueada',
        ])->assertRedirect()
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'El contacto no tiene consentimiento vigente'));

        $this->actingAs($operator)->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Envio bloqueado')
            ->assertSee('Sin consentimiento')
            ->assertSee('El contacto no tiene consentimiento vigente para este canal.');
    }

    public function test_provider_error_marks_text_message_as_failed(): void
    {
        $contact = $this->makeContact('573101000014', true);
        $conversation = Conversation::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $conversation->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'queued',
            'body' => 'Hola',
        ]);

        $provider = new class implements MessagingProviderInterface {
            public function sendText(SendTextMessageDTO $dto): array
            {
                return [
                    'error' => [
                        'message' => '(#131005) Access denied',
                        'code' => 131005,
                        'error_data' => [
                            'details' => 'There was a problem with the access token or permissions.',
                        ],
                    ],
                ];
            }

            public function sendImage(SendImageMessageDTO $dto): array
            {
                return [];
            }

            public function sendDocument(SendDocumentMessageDTO $dto): array
            {
                return [];
            }

            public function sendTemplate(SendTemplateMessageDTO $dto): array
            {
                return [];
            }

            public function uploadMedia(UploadMediaDTO $dto): array
            {
                return [];
            }

            public function parseWebhook(array $payload): array
            {
                return $payload;
            }
        };

        (new SendWhatsAppTextJob($message->id))->handle($provider, app(MessageStatusService::class));

        $this->assertDatabaseHas('messages', ['id' => $message->id, 'status' => 'failed']);
        $this->assertDatabaseHas('message_events', ['message_id' => $message->id, 'status' => 'failed', 'event_type' => 'provider_failed']);

        $this->actingAs($this->admin)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Error de Meta')
            ->assertSee('131005')
            ->assertSee('Access denied')
            ->assertSee('There was a problem with the access token or permissions.');

        $this->actingAs($this->admin)->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Error de Meta')
            ->assertSee('131005')
            ->assertSee('Access denied');
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

    private function openCustomerCareWindow(Contact $contact, mixed $createdAt = null): InboundMessage
    {
        $createdAt ??= now();

        $message = InboundMessage::create([
            'contact_id' => $contact->id,
            'channel_id' => $this->channel->id,
            'provider_message_id' => 'wamid-window-'.$contact->primary_phone.'-'.Str::uuid(),
            'from_phone' => $contact->primary_phone,
            'body' => 'Mensaje entrante',
            'payload' => ['type' => 'text'],
        ]);

        $message->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $message;
    }
}
