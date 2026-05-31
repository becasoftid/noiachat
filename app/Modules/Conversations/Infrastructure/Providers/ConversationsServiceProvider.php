<?php

namespace App\Modules\Conversations\Infrastructure\Providers;

use App\Modules\Conversations\Domain\Repositories\ConversationRepositoryInterface;
use App\Modules\Conversations\Infrastructure\Persistence\Repositories\EloquentConversationRepository;
use Illuminate\Support\ServiceProvider;

class ConversationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ConversationRepositoryInterface::class, EloquentConversationRepository::class);
    }
}
