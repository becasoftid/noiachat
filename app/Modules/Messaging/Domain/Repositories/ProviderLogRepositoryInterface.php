<?php

namespace App\Modules\Messaging\Domain\Repositories;

use App\Modules\Messaging\Infrastructure\Persistence\Models\ProviderLog;

interface ProviderLogRepositoryInterface
{
    public function firstOrCreate(array $identity, array $values): ProviderLog;
}
