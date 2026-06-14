<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Consents\Application\UseCases\GrantConsentUseCase;
use App\Modules\Consents\Infrastructure\Persistence\Models\ContactBlacklist;
use App\Modules\Contacts\Application\DTOs\UpsertContactDTO;
use App\Modules\Contacts\Application\Services\ContactService;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Contact;
use App\Modules\Conversations\Infrastructure\Persistence\Models\Conversation;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use App\Modules\Users\Infrastructure\Persistence\Models\Role;
use App\Modules\Webhooks\Infrastructure\Persistence\Models\OptOutRequest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMergeTest extends TestCase
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

    public function test_admin_can_merge_contact_history_into_target_contact(): void
    {
        $source = $this->makeContact('573009990001');
        $target = $this->makeContact('573009990002');
        app(GrantConsentUseCase::class)->execute($source, $this->channel->id, 'manual', $this->admin->id);
        ContactBlacklist::create([
            'contact_id' => $source->id,
            'channel_id' => $this->channel->id,
            'reason' => 'manual',
            'created_by_user_id' => $this->admin->id,
            'created_at' => now(),
        ]);

        $targetConversation = Conversation::create([
            'contact_id' => $target->id,
            'channel_id' => $this->channel->id,
            'status' => 'open',
            'last_message_at' => now()->subMinutes(2),
        ]);
        $sourceConversation = Conversation::create([
            'contact_id' => $source->id,
            'channel_id' => $this->channel->id,
            'status' => 'pending',
            'last_message_at' => now(),
        ]);
        $message = Message::create([
            'contact_id' => $source->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $sourceConversation->id,
            'user_id' => $this->admin->id,
            'type' => 'text',
            'status' => 'sent',
            'body' => 'Historial origen',
        ]);
        $inbound = InboundMessage::create([
            'contact_id' => $source->id,
            'channel_id' => $this->channel->id,
            'conversation_id' => $sourceConversation->id,
            'provider_message_id' => 'wamid-merge-1',
            'from_phone' => $source->primary_phone,
            'body' => 'Entrada origen',
            'payload' => [],
        ]);
        OptOutRequest::create([
            'inbound_message_id' => $inbound->id,
            'contact_id' => $source->id,
            'channel_id' => $this->channel->id,
            'keyword' => 'STOP',
            'requested_at' => now(),
        ]);

        $this->actingAs($this->admin)->post(route('contacts.merge', $source), [
            'target_contact_id' => $target->id,
        ])->assertRedirect(route('contacts.show', $target));

        $this->assertSoftDeleted('contacts', ['id' => $source->id]);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'contact_id' => $target->id,
            'conversation_id' => $targetConversation->id,
        ]);
        $this->assertDatabaseHas('inbound_messages', [
            'id' => $inbound->id,
            'contact_id' => $target->id,
            'conversation_id' => $targetConversation->id,
        ]);
        $this->assertDatabaseHas('contact_consents', ['contact_id' => $target->id]);
        $this->assertDatabaseHas('contact_blacklist', ['contact_id' => $target->id]);
        $this->assertDatabaseHas('opt_out_requests', ['contact_id' => $target->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $sourceConversation->id]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->admin->id,
            'action' => 'update',
            'module' => 'contacts',
            'target_type' => Contact::class,
            'target_id' => $target->id,
        ]);
    }

    public function test_operator_cannot_merge_contacts(): void
    {
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::query()->where('name', 'operator')->firstOrFail()->id);
        $source = $this->makeContact('573009990003');
        $target = $this->makeContact('573009990004');

        $this->actingAs($operator)->post(route('contacts.merge', $source), [
            'target_contact_id' => $target->id,
        ])->assertForbidden();
    }

    public function test_admin_cannot_merge_contact_into_itself(): void
    {
        $contact = $this->makeContact('573009990005');

        $this->actingAs($this->admin)->post(route('contacts.merge', $contact), [
            'target_contact_id' => $contact->id,
        ])->assertSessionHasErrors('target_contact_id');
    }

    private function makeContact(string $phone): Contact
    {
        return app(ContactService::class)->create(new UpsertContactDTO('Contacto', $phone, null, $phone), $this->admin->id);
    }
}
