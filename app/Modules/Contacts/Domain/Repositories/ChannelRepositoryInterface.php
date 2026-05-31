<?php

namespace App\Modules\Contacts\Domain\Repositories;

use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use Illuminate\Support\Collection;

interface ChannelRepositoryInterface
{
    public function findBySlug(string $slug): ?Channel;

    public function active(): Collection;
}
