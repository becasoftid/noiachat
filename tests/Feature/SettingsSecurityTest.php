<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsSecurityTest extends TestCase
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

    public function test_settings_page_masks_channel_secrets(): void
    {
        $this->channel->update([
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'access_token' => 'secret-access-token-value-123456',
                'webhook_verify_token' => 'webhook-token-secret',
                'app_secret' => 'app-secret-value-123456',
            ],
        ]);

        $this->actingAs($this->admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertDontSee('secret-access-token-value-123456')
            ->assertDontSee('webhook-token-secret')
            ->assertDontSee('app-secret-value-123456')
            ->assertSee('Configurado');
    }

    public function test_channel_update_preserves_existing_secrets_when_secret_fields_are_blank(): void
    {
        $this->channel->update([
            'settings' => [
                'provider' => 'whatsapp_cloud',
                'api_base_url' => 'https://graph.facebook.com/v21.0',
                'phone_number_id' => '123456789012345',
                'business_account_id' => '987654321098765',
                'access_token' => 'secret-access-token-value-123456',
                'webhook_verify_token' => 'webhook-token-secret',
                'app_secret' => 'app-secret-value-123456',
            ],
        ]);

        $this->actingAs($this->admin)
            ->put(route('settings.channels.update', $this->channel), [
                'name' => 'WhatsApp Produccion',
                'is_active' => '1',
                'settings' => [
                    'provider' => 'whatsapp_cloud',
                    'api_base_url' => 'https://graph.facebook.com/v22.0',
                    'phone_number_id' => '123456789012345',
                    'business_account_id' => '987654321098765',
                    'access_token' => '',
                    'webhook_verify_token' => '',
                    'app_secret' => '',
                ],
            ])
            ->assertRedirect();

        $settings = $this->channel->fresh()->settings;

        $this->assertSame('secret-access-token-value-123456', $settings['access_token']);
        $this->assertSame('webhook-token-secret', $settings['webhook_verify_token']);
        $this->assertSame('app-secret-value-123456', $settings['app_secret']);
        $this->assertSame('https://graph.facebook.com/v22.0', $settings['api_base_url']);
    }

    public function test_channel_update_rejects_insecure_url_and_invalid_meta_ids(): void
    {
        $this->actingAs($this->admin)
            ->from(route('settings.index'))
            ->put(route('settings.channels.update', $this->channel), [
                'name' => 'WhatsApp',
                'is_active' => '1',
                'settings' => [
                    'provider' => 'whatsapp_cloud',
                    'api_base_url' => 'http://graph.facebook.com/v22.0',
                    'phone_number_id' => 'phone-id',
                    'business_account_id' => 'waba-id',
                ],
            ])
            ->assertRedirect(route('settings.index'))
            ->assertSessionHasErrors([
                'settings.api_base_url',
                'settings.phone_number_id',
                'settings.business_account_id',
            ]);
    }

    public function test_channel_update_stores_token_rotation_metadata(): void
    {
        $this->actingAs($this->admin)
            ->put(route('settings.channels.update', $this->channel), [
                'name' => 'WhatsApp',
                'is_active' => '1',
                'settings' => [
                    'provider' => 'whatsapp_cloud',
                    'api_base_url' => 'https://graph.facebook.com/v22.0',
                    'phone_number_id' => '123456789012345',
                    'business_account_id' => '987654321098765',
                    'access_token' => 'secret-access-token-value-123456',
                    'webhook_verify_token' => 'webhook-token-secret',
                    'app_secret' => 'app-secret-value-123456',
                    'access_token_expires_at' => '2026-12-31',
                    'access_token_rotated_at' => '2026-06-17',
                    'access_token_responsible' => 'Equipo Operaciones',
                    'access_token_rotation_procedure' => 'Crear token en Meta, guardar en canal, sincronizar plantillas y probar envio.',
                ],
            ])
            ->assertRedirect();

        $settings = $this->channel->fresh()->settings;

        $this->assertSame('2026-12-31', $settings['access_token_expires_at']);
        $this->assertSame('2026-06-17', $settings['access_token_rotated_at']);
        $this->assertSame('Equipo Operaciones', $settings['access_token_responsible']);
        $this->assertStringContainsString('sincronizar plantillas', $settings['access_token_rotation_procedure']);
    }
}
