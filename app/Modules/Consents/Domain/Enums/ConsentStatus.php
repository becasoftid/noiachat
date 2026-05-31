<?php

namespace App\Modules\Consents\Domain\Enums;

enum ConsentStatus: string
{
    case GRANTED = 'granted';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
}
