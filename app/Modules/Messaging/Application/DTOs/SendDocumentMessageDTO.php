<?php

namespace App\Modules\Messaging\Application\DTOs;

class SendDocumentMessageDTO
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $to,
        public readonly string $mediaUrl,
        public readonly string $filename,
        public readonly ?string $caption = null,
    ) {}
}
