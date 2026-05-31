<?php

namespace App\Modules\Audit\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Audit\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user')
            ->when($filters['module'] ?? null, fn ($q, $value) => $q->where('module', $value))
            ->when($filters['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when($filters['target_type'] ?? null, fn ($q, $value) => $q->where('target_type', 'like', "%{$value}%"))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate($perPage);
    }

    public function usersForFilter(): Collection
    {
        return User::query()->orderBy('name')->get();
    }

    public function moduleSummary(array $filters = []): Collection
    {
        return AuditLog::query()
            ->selectRaw('module, count(*) as total')
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();
    }
}
