<?php

namespace App\Modules\Messaging\Application\DTOs;

class UploadMediaDTO
{
    public function __construct(
        public readonly string $path,
        public readonly string $mimeType,
    ) {}
}
