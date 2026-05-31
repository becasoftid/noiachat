<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Repositories;

use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Support\Collection;

class EloquentChannelRepository implements ChannelRepositoryInterface
{
    public function findBySlug(string $slug): ?Channel
    {
        return Channel::query()->where('slug', $slug)->first();
    }

    public function active(): Collection
    {
        return Channel::query()->where('is_active', true)->get();
    }
}
