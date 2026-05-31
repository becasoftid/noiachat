<?php

namespace App\Modules\Shared\Infrastructure\Providers;

use App\Modules\Shared\Application\Services\AuditLogger;
use App\Modules\Shared\Domain\Contracts\FileStorageInterface;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileStorageInterface::class, LocalFileStorage::class);
        $this->app->singleton(AuditLogger::class, AuditLogger::class);
    }
}
