<?php

namespace App\Modules\Compliance\Domain\Enums;

enum EligibilityStatus: string
{
    case ALLOWED = 'allowed';
    case BLOCKED_NO_CONSENT = 'blocked_no_consent';
    case BLOCKED_BLACKLIST = 'blocked_blacklist';
    case BLOCKED_INVALID_CONTACT = 'blocked_invalid_contact';
    case BLOCKED_FREQUENCY = 'blocked_frequency';
    case BLOCKED_CHANNEL_INACTIVE = 'blocked_channel_inactive';
    case BLOCKED_TEMPLATE_INACTIVE = 'blocked_template_inactive';
}
