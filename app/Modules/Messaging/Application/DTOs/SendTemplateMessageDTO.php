<?php

namespace App\Modules\Messaging\Application\DTOs;

readonly class SendTemplateMessageDTO
{
    public function __construct(
        public string $messageId,
        public string $to,
        public string $templateName,
        public string $language,
        public array $parameters = [],
    ) {}
}
