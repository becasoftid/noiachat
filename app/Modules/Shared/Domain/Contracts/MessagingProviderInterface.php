<?php

namespace App\Modules\Shared\Domain\Contracts;

use App\Modules\Messaging\Application\DTOs\SendDocumentMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendImageMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTemplateMessageDTO;
use App\Modules\Messaging\Application\DTOs\SendTextMessageDTO;
use App\Modules\Messaging\Application\DTOs\UploadMediaDTO;

interface MessagingProviderInterface
{
    public function sendText(SendTextMessageDTO $dto): array;

    public function sendImage(SendImageMessageDTO $dto): array;

    public function sendDocument(SendDocumentMessageDTO $dto): array;

    public function sendTemplate(SendTemplateMessageDTO $dto): array;

    public function uploadMedia(UploadMediaDTO $dto): array;

    public function parseWebhook(array $payload): array;
}
