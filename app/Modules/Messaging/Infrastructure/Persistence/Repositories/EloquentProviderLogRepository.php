<?php

namespace App\Modules\Messaging\Infrastructure\Persistence\Repositories;

use App\Modules\Messaging\Domain\Repositories\ProviderLogRepositoryInterface;
use App\Modules\Messaging\Infrastructure\Persistence\Models\ProviderLog;

class EloquentProviderLogRepository implements ProviderLogRepositoryInterface
{
    public function firstOrCreate(array $identity, array $values): ProviderLog
    {
        return ProviderLog::firstOrCreate($identity, $values);
    }
}
