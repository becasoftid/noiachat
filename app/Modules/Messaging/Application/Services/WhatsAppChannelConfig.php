<?php

namespace App\Modules\Messaging\Application\Services;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Messaging\Infrastructure\Persistence\Models\Message;
use Illuminate\Support\Collection;

class WhatsAppChannelConfig
{
    public function forChannel(?Channel $channel): array
    {
        $settings = $channel?->settings ?? [];

        return [
            'api_base_url' => $this->value($settings, 'api_base_url', (string) config('services.whatsapp.api_base_url', 'https://graph.facebook.com/v21.0')),
            'access_token' => $this->value($settings, 'access_token', (string) config('services.whatsapp.access_token', '')),
            'phone_number_id' => $this->value($settings, 'phone_number_id', (string) config('services.whatsapp.phone_number_id', '')),
            'business_account_id' => $this->value($settings, 'business_account_id', (string) config('services.whatsapp.business_account_id', '')),
            'webhook_verify_token' => $this->value($settings, 'webhook_verify_token', (string) config('services.whatsapp.webhook_verify_token', '')),
            'app_secret' => $this->value($settings, 'app_secret', (string) config('services.whatsapp.app_secret', '')),
        ];
    }

    public function forMessageId(string $messageId): array
    {
        $message = Message::query()->with('channel')->find($messageId);

        return $this->forChannel($message?->channel);
    }

    public function findChannelByPhoneNumberId(?string $phoneNumberId): ?Channel
    {
        if (blank($phoneNumberId)) {
            return $this->defaultChannel();
        }

        return Channel::query()
            ->where('slug', 'whatsapp')
            ->where('is_active', true)
            ->where('settings->phone_number_id', $phoneNumberId)
            ->first();
    }

    public function channelForWebhookPayload(array $payload): ?Channel
    {
        return $this->findChannelByPhoneNumberId(
            data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id')
        );
    }

    public function verifyTokens(): Collection
    {
        return $this->activeWhatsAppChannels()
            ->map(fn (Channel $channel): string => $this->forChannel($channel)['webhook_verify_token'])
            ->push((string) config('services.whatsapp.webhook_verify_token', ''))
            ->filter()
            ->unique()
            ->values();
    }

    public function appSecrets(): Collection
    {
        return $this->activeWhatsAppChannels()
            ->map(fn (Channel $channel): string => $this->forChannel($channel)['app_secret'])
            ->push((string) config('services.whatsapp.app_secret', ''))
            ->filter()
            ->unique()
            ->values();
    }

    public function missingForSending(array $config): array
    {
        return collect(['access_token', 'phone_number_id'])
            ->filter(fn (string $key): bool => blank($config[$key] ?? null))
            ->values()
            ->all();
    }

    public function missingForTemplateSync(array $config): array
    {
        return collect(['access_token', 'business_account_id'])
            ->filter(fn (string $key): bool => blank($config[$key] ?? null))
            ->values()
            ->all();
    }

    private function activeWhatsAppChannels(): Collection
    {
        return Channel::query()
            ->where('slug', 'whatsapp')
            ->where('is_active', true)
            ->get();
    }

    private function defaultChannel(): ?Channel
    {
        return Channel::query()
            ->where('slug', 'whatsapp')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    private function value(array $settings, string $key, string $fallback): string
    {
        $value = data_get($settings, $key);

        return blank($value) ? $fallback : (string) $value;
    }
}
