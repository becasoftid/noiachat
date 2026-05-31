<?php

namespace App\Modules\Contacts\Application\DTOs;

class UpsertContactDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $email,
        public readonly string $primaryPhone,
        public readonly string $status = 'active',
    ) {}
}
