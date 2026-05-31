<?php

namespace App\Modules\Messaging\Application\DTOs;

class SendImageMessageDTO
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $to,
        public readonly string $mediaUrl,
        public readonly ?string $caption = null,
    ) {}
}
