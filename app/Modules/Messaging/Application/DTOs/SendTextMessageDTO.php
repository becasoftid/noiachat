<?php

namespace App\Modules\Messaging\Application\DTOs;

class SendTextMessageDTO
{
    public function __construct(
        public readonly string $messageId,
        public readonly string $to,
        public readonly string $body,
    ) {}
}
