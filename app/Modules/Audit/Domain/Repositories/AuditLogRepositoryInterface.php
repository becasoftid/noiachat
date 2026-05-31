<?php

namespace App\Modules\Audit\Domain\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AuditLogRepositoryInterface
{
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function usersForFilter(): Collection;

    public function moduleSummary(array $filters = []): Collection;
}
