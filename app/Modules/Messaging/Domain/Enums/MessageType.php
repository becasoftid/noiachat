<?php

namespace App\Modules\Messaging\Domain\Enums;

enum MessageType: string
{
    case TEXT = 'text';
    case IMAGE = 'image';
    case DOCUMENT = 'document';
    case TEMPLATE = 'template';
}
