<?php

namespace App\Modules\Messaging\Infrastructure\Providers;

use App\Modules\Messaging\Domain\Repositories\InboundMessageRepositoryInterface;
use App\Modules\Messaging\Domain\Repositories\MessageRepositoryInterface;
use App\Modules\Messaging\Domain\Repositories\ProviderLogRepositoryInterface;
use App\Modules\Messaging\Infrastructure\Integrations\WhatsAppCloudApiProvider;
use App\Modules\Messaging\Infrastructure\Persistence\Repositories\EloquentInboundMessageRepository;
use App\Modules\Messaging\Infrastructure\Persistence\Repositories\EloquentMessageRepository;
use App\Modules\Messaging\Infrastructure\Persistence\Repositories\EloquentProviderLogRepository;
use App\Modules\Shared\Domain\Contracts\MessagingProviderInterface;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MessagingProviderInterface::class, WhatsAppCloudApiProvider::class);
        $this->app->bind(MessageRepositoryInterface::class, EloquentMessageRepository::class);
        $this->app->bind(InboundMessageRepositoryInterface::class, EloquentInboundMessageRepository::class);
        $this->app->bind(ProviderLogRepositoryInterface::class, EloquentProviderLogRepository::class);
    }
}
