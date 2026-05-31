<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Repositories;

use App\Modules\Messaging\Domain\Repositories\InboundMessageRepositoryInterface;
use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;

class EloquentInboundMessageRepository implements InboundMessageRepositoryInterface
{
    public function firstOrCreate(array $identity, array $values): InboundMessage
    {
        return InboundMessage::firstOrCreate($identity, $values);
    }
}
