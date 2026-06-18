<?php

namespace App\Modules\Contacts\Infrastructure\Persistence\Repositories;

use App\Modules\Contacts\Domain\Repositories\ChannelRepositoryInterface;
use App\Modules\Contacts\Infrastructure\Persistence\Models\Channel;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentChannelRepository implements ChannelRepositoryInterface
{
    public function findBySlug(string $slug): ?Channel
    {
        return $this->query()->where('slug', $slug)->first();
    }

    public function active(): Collection
    {
        return $this->query()->where('is_active', true)->get();
    }

    private function query(): Builder
    {
        $query = Channel::query();
        $context = app(TenantContext::class);

        return $context->companyId() !== null ? $query->forTenantContext($context) : $query;
    }
}
