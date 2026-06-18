<?php

namespace App\Modules\Audit\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audit\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Audit\Infrastructure\Persistence\Models\AuditLog;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogRepositoryInterface $auditLogs) {}

    public function index(Request $request)
    {
        $filters = $request->only(['module', 'action', 'user_id', 'target_type', 'date_from', 'date_to']);
        $branches = $this->tenantBranches();
        $branchId = $request->string('branch_id')->toString();

        if ($branchId !== '' && $branches->contains('id', $branchId)) {
            $filters['branch_id'] = $branchId;
        }

        return view('noia.audit.index', [
            'logs' => $this->auditLogs->paginateFiltered($filters),
            'summary' => $this->auditLogs->moduleSummary($filters),
            'users' => $this->auditLogs->usersForFilter(),
            'branches' => $branches,
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['user', 'company', 'branch']);

        return view('noia.audit.show', [
            'log' => $auditLog,
            'changes' => $this->changes($auditLog),
        ]);
    }

    private function changes(AuditLog $auditLog): Collection
    {
        $oldValues = $auditLog->old_values_json ?? [];
        $newValues = $auditLog->new_values_json ?? [];
        $keys = collect(array_keys($oldValues))
            ->merge(array_keys($newValues))
            ->unique()
            ->sort()
            ->values();

        return $keys->map(fn (string $key) => [
            'field' => $key,
            'old' => $oldValues[$key] ?? null,
            'new' => $newValues[$key] ?? null,
            'changed' => ($oldValues[$key] ?? null) !== ($newValues[$key] ?? null),
        ]);
    }

    private function tenantBranches(): Collection
    {
        $context = app(TenantContext::class);

        if ($context->companyId() === null || $context->branchId() !== null) {
            return collect();
        }

        return Branch::query()
            ->where('company_id', $context->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
