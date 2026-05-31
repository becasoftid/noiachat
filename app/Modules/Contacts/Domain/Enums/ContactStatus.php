<?php

namespace App\Modules\Contacts\Domain\Enums;

enum ContactStatus: string
{
    case ACTIVE = 'active';
    case BLOCKED = 'blocked';
    case NO_CONTACT = 'no_contact';
    case INVALID = 'invalid';
}
