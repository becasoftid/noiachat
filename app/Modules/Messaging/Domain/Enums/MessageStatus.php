<?php

namespace App\Modules\Messaging\Domain\Enums;

enum MessageStatus: string
{
    case DRAFT = 'draft';
    case QUEUED = 'queued';
    case SENDING = 'sending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case READ = 'read';
    case FAILED = 'failed';
    case BOUNCED = 'bounced';
    case CANCELLED = 'cancelled';
    case BLOCKED_BY_POLICY = 'blocked_by_policy';
}
