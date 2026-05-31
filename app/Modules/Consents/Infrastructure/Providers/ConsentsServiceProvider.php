<?php

namespace App\Modules\Consents\Infrastructure\Providers;

use App\Modules\Consents\Domain\Repositories\BlacklistRepositoryInterface;
use App\Modules\Consents\Domain\Repositories\ConsentRepositoryInterface;
use App\Modules\Consents\Infrastructure\Persistence\Repositories\EloquentBlacklistRepository;
use App\Modules\Consents\Infrastructure\Persistence\Repositories\EloquentConsentRepository;
use Illuminate\Support\ServiceProvider;

class ConsentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConsentRepositoryInterface::class, EloquentConsentRepository::class);
        $this->app->bind(BlacklistRepositoryInterface::class, EloquentBlacklistRepository::class);
    }
}
