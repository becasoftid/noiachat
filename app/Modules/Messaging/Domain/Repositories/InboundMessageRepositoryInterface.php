<?php

namespace App\Modules\Messaging\Domain\Repositories;

use App\Modules\Messaging\Infrastructure\Persistence\Models\InboundMessage;

interface InboundMessageRepositoryInterface
{
    public function firstOrCreate(array $identity, array $values): InboundMessage;
}
