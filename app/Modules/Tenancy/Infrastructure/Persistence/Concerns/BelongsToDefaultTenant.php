<?php

namespace App\Modules\Tenancy\Infrastructure\Persistence\Concerns;

use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Application\Support\TenancyDefaults;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Branch;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

trait BelongsToDefaultTenant
{
    protected static function bootBelongsToDefaultTenant(): void
    {
        static::creating(function ($model): void {
            $table = $model->getTable();
            $context = app()->bound(TenantContext::class) ? app(TenantContext::class) : null;
            $companyId = $context?->companyId();
            $branchId = $context?->branchId();

            if (Schema::hasColumn($table, 'company_id') && blank($model->company_id)) {
                $model->company_id = $companyId ?? TenancyDefaults::companyId();
            }

            if (Schema::hasColumn($table, 'branch_id') && blank($model->branch_id)) {
                $model->branch_id = $branchId ?? TenancyDefaults::branchId($model->company_id);
            }
        });
    }

    public function scopeForTenantContext(Builder $query, ?TenantContext $context = null): Builder
    {
        $context ??= app(TenantContext::class);
        $companyId = $context->companyId();

        if ($companyId === null) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($query->getModel()->getTable().'.company_id', $companyId);

        if ($context->branchId() !== null) {
            $query->where(function (Builder $branchQuery) use ($context): void {
                $branchQuery
                    ->where($branchQuery->getModel()->getTable().'.branch_id', $context->branchId())
                    ->orWhereNull($branchQuery->getModel()->getTable().'.branch_id');
            });
        }

        return $query;
    }

    public function belongsToActiveTenant(?TenantContext $context = null): bool
    {
        $context ??= app(TenantContext::class);

        if ($context->companyId() === null || $this->company_id !== $context->companyId()) {
            return false;
        }

        if ($context->branchId() === null) {
            return true;
        }

        return blank($this->branch_id) || $this->branch_id === $context->branchId();
    }

    protected function hasActiveTenantContext(): bool
    {
        return app()->bound(TenantContext::class) && app(TenantContext::class)->companyId() !== null;
    }

    public function tenantAttributes(): array
    {
        return [
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
