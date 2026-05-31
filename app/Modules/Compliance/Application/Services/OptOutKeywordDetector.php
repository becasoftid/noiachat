<?php

namespace App\Modules\Compliance\Application\Services;

class OptOutKeywordDetector
{
    private const KEYWORDS = ['STOP', 'BAJA', 'CANCELAR', 'NO MAS', 'NO DESEO', 'NO ENVIAR'];

    public function detect(?string $text): ?string
    {
        $normalized = strtoupper(trim(str_replace('Á', 'A', $text ?? '')));

        foreach (self::KEYWORDS as $keyword) {
            if (str_contains($normalized, $keyword)) {
                return $keyword;
            }
        }

        return null;
    }
}
