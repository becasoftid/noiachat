<?php

namespace App\Modules\Messaging\Infrastructure\Integrations;

use App\Modules\Messaging\Application\DTOs\SendDocumentMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendImageMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTemplateMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTextMessageDTO;
use App\Modules\Messaging\Application\DTOs\UploadMediaDTO;
use App\Modules\Messaging\Application\Services\WhatsAppChannelConfig;
use App\Modules\Shared\Domain\Contracts\MessagingProviderInterface;
use Illuminate\Support\Facades\Http;

class WhatsAppCloudApiProvider implements MessagingProviderInterface
{
    public function __construct(private readonly WhatsAppChannelConfig $channelConfig)
    {
    }

    public function sendText(SendTextMessageDTO $dto): array
    {
        return $this->request($dto->messageId, ['messaging_product' => 'whatsapp', 'to' => $dto->to, 'type' => 'text', 'text' => ['body' => $dto->body]]);
    }

    public function sendImage(SendImageMessageDTO $dto): array
    {
        return $this->request($dto->messageId, ['messaging_product' => 'whatsapp', 'to' => $dto->to, 'type' => 'image', 'image' => ['link' => $dto->mediaUrl, 'caption' => $dto->caption]]);
    }

    public function sendDocument(SendDocumentMessageDTO $dto): array
    {
        return $this->request($dto->messageId, ['messaging_product' => 'whatsapp', 'to' => $dto->to, 'type' => 'document', 'document' => ['link' => $dto->mediaUrl, 'caption' => $dto->caption, 'filename' => $dto->filename]]);
    }

    public function sendTemplate(SendTemplateMessageDTO $dto): array
    {
        return $this->request($dto->messageId, [
            'messaging_product' => 'whatsapp',
            'to' => $dto->to,
            'type' => 'template',
            'template' => [
                'name' => $dto->templateName,
                'language' => ['code' => $dto->language],
                'components' => $dto->parameters === []
                    ? []
                    : [[
                        'type' => 'body',
                        'parameters' => collect($dto->parameters)
                            ->values()
                            ->map(fn ($value) => ['type' => 'text', 'text' => (string) $value])
                            ->all(),
                    ]],
            ],
        ]);
    }

    public function uploadMedia(UploadMediaDTO $dto): array
    {
        return ['path' => $dto->path, 'mime_type' => $dto->mimeType];
    }

    public function parseWebhook(array $payload): array
    {
        return $payload;
    }

    private function request(string $messageId, array $payload): array
    {
        $config = $this->channelConfig->forMessageId($messageId);
        $missing = $this->channelConfig->missingForSending($config);

        if ($missing !== []) {
            return [
                'error' => [
                    'code' => 'missing_whatsapp_channel_config',
                    'message' => 'Faltan credenciales de WhatsApp para el canal.',
                    'missing' => $missing,
                ],
            ];
        }

        $baseUrl = rtrim($config['api_base_url'], '/');
        $phoneNumberId = $config['phone_number_id'];

        return Http::withToken($config['access_token'])
            ->post($baseUrl.'/'.$phoneNumberId.'/messages', $payload)
            ->json() ?? [];
    }
}
