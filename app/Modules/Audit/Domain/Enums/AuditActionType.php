<?php

namespace App\Modules\Audit\Domain\Enums;

enum AuditActionType: string
{
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case SEND = 'send';
    case BLOCK = 'block';
    case REVOKE_CONSENT = 'revoke_consent';
    case IMPORT = 'import';
    case RETRY = 'retry';
    case WEBHOOK_PROCESSED = 'webhook_processed';
}
