<?php

namespace App\Modules\Conversations\Domain\Enums;

enum ConversationStatus: string
{
    case OPEN = 'open';
    case PENDING = 'pending';
    case RESOLVED = 'resolved';
    case CLOSED = 'closed';
}
