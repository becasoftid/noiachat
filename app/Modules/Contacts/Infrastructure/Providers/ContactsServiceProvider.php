<?php

namespace App\Modules\Contacts\Infrastructure\Providers;

use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Domain\Repositories\ContactRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Repositories\EloquentChannelRepository;
use App\Modules\Contacts\Infrastructure\Persistence\Repositories\EloquentContactRepository;
use Illuminate\Support\ServiceProvider;

class ContactsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ContactRepositoryInterface::class, EloquentContactRepository::class);
        $this->app->bind(ChannelRepositoryInterface::class, EloquentChannelRepository::class);
    }
}
