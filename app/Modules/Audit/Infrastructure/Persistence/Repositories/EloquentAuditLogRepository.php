<?php

namespace App\Modules\Audit\Infrastructure\Persistence\Repositories;

use App\Models\User;
use App\Modules\Audit\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function paginateFiltered(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query()
            ->with('user')
            ->when($filters['module'] ?? null, fn ($q, $value) => $q->where('module', $value))
            ->when($filters['action'] ?? null, fn ($q, $value) => $q->where('action', $value))
            ->when($filters['user_id'] ?? null, fn ($q, $value) => $q->where('user_id', $value))
            ->when(array_key_exists('branch_id', $filters), function ($q) use ($filters): void {
                blank($filters['branch_id'])
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['target_type'] ?? null, fn ($q, $value) => $q->where('target_type', 'like', "%{$value}%"))
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->latest()
            ->paginate($perPage);
    }

    public function usersForFilter(): Collection
    {
        $context = app(TenantContext::class);

        return User::query()
            ->whereHas('memberships', function ($query) use ($context): void {
                $query->where('company_id', $context->companyId())
                    ->where('is_active', true);

                if ($context->branchId() !== null) {
                    $query->where(function ($branchQuery) use ($context): void {
                        $branchQuery->where('branch_id', $context->branchId())
                            ->orWhereNull('branch_id');
                    });
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function moduleSummary(array $filters = []): Collection
    {
        return $this->query()
            ->selectRaw('module, count(*) as total')
            ->when(array_key_exists('branch_id', $filters), function ($q) use ($filters): void {
                blank($filters['branch_id'])
                    ? $q->whereNull('branch_id')
                    : $q->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['date_from'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($q, $value) => $q->whereDate('created_at', '<=', $value))
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();
    }

    private function query(): Builder
    {
        $query = AuditLog::query();
        $context = app(TenantContext::class);

        return $context->companyId() !== null ? $query->forTenantContext($context) : $query;
    }
}
