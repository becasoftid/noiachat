<?php

namespace App\Modules\Messaging\Application\Services;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class WhatsAppConnectionTestService
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly WhatsAppChannelConfig $channelConfig,
    ) {}

    public function test(Channel $channel): array
    {
        $config = $this->channelConfig->forChannel($channel);
        $missing = collect(['access_token', 'phone_number_id', 'business_account_id'])
            ->filter(fn (string $key): bool => blank($config[$key] ?? null))
            ->values()
            ->all();

        if ($missing !== []) {
            throw new RuntimeException('Faltan credenciales para probar Meta: '.implode(', ', $missing).'.');
        }

        $baseUrl = rtrim($config['api_base_url'], '/');
        $phoneResponse = $this->http
            ->withToken($config['access_token'])
            ->acceptJson()
            ->get($baseUrl.'/'.$config['phone_number_id'], [
                'fields' => 'id,display_phone_number,verified_name',
            ]);

        if (! $phoneResponse->successful()) {
            $message = $phoneResponse->json('error.message') ?: 'Meta no valido el Phone Number ID.';

            throw new RuntimeException('No se pudo validar el numero de WhatsApp: '.$message);
        }

        $wabaResponse = $this->http
            ->withToken($config['access_token'])
            ->acceptJson()
            ->get($baseUrl.'/'.$config['business_account_id'], [
                'fields' => 'id,name',
            ]);

        if (! $wabaResponse->successful()) {
            $message = $wabaResponse->json('error.message') ?: 'Meta no valido la cuenta WhatsApp Business.';

            throw new RuntimeException('No se pudo validar la cuenta WhatsApp Business: '.$message);
        }

        return [
            'phone_number_id' => $phoneResponse->json('id') ?: $config['phone_number_id'],
            'display_phone_number' => $phoneResponse->json('display_phone_number'),
            'verified_name' => $phoneResponse->json('verified_name'),
            'business_account_id' => $wabaResponse->json('id') ?: $config['business_account_id'],
            'business_name' => $wabaResponse->json('name'),
            'tested_at' => now()->toISOString(),
        ];
    }
}
