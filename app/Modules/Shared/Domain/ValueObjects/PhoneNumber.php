<?php

namespace App\Modules\Shared\Domain\ValueObjects;

use App\Modules\Shared\Domain\Exceptions\BusinessRuleException;

final class PhoneNumber
{
    public function __construct(private readonly string $value) {}

    public static function from(string $value): self
    {
        $normalized = preg_replace('/\D+/', '', $value) ?? '';

        if ($normalized === '' || strlen($normalized) < 8) {
            throw new BusinessRuleException('Invalid phone number.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
