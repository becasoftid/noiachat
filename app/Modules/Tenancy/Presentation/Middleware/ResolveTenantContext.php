<?php

namespace App\Modules\Tenancy\Presentation\Middleware;

use App\Modules\Tenancy\Application\Services\DefaultMembershipService;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Membership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly DefaultMembershipService $defaultMembershipService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $this->defaultMembershipService->ensureFor($user);

        $memberships = Membership::query()
            ->with(['company', 'branch', 'role'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('status', 'active'))
            ->where(function ($query): void {
                $query->whereNull('branch_id')
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('is_active', true));
            })
            ->orderByDesc('is_default')
            ->orderBy('company_id')
            ->orderBy('branch_id')
            ->get();

        $this->tenantContext->setMemberships($memberships);

        $membership = $this->resolveMembership($request, $memberships);
        $this->tenantContext->setMembership($membership);

        if ($membership !== null) {
            $request->session()->put([
                'tenant.membership_id' => $membership->id,
                'tenant.company_id' => $membership->company_id,
                'tenant.branch_id' => $membership->branch_id,
            ]);
        }

        View::share('tenantContext', $this->tenantContext);

        return $next($request);
    }

    private function resolveMembership(Request $request, $memberships): ?Membership
    {
        if ($memberships->isEmpty()) {
            return null;
        }

        $sessionMembershipId = $request->session()->get('tenant.membership_id');

        if ($sessionMembershipId !== null) {
            $selected = $memberships->firstWhere('id', (int) $sessionMembershipId);

            if ($selected !== null) {
                return $selected;
            }
        }

        return $memberships->firstWhere('is_default', true) ?? $memberships->first();
    }
}
