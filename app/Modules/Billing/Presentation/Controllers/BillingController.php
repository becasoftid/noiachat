<?php

namespace App\Modules\Billing\Presentation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Application\Services\SubscriptionOverviewService;
use App\Modules\Billing\Infrastructure\Persistence\Models\CompanySubscription;
use App\Modules\Billing\Infrastructure\Persistence\Models\Plan;
use App\Modules\Billing\Infrastructure\Persistence\Models\SubscriptionChangeRequest;
use App\Modules\Tenancy\Application\Services\TenantContext;
use App\Modules\Tenancy\Infrastructure\Persistence\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function index(TenantContext $tenantContext, SubscriptionOverviewService $overview)
    {
        $company = $tenantContext->company();

        abort_if($company === null, 403);

        return view('noia.billing.index', [
            'company' => $company,
            'overview' => $overview->forCompany($company),
            'plans' => $this->commercialPlans(),
            'changeRequests' => SubscriptionChangeRequest::query()
                ->with(['currentPlan', 'requestedPlan', 'requester'])
                ->where('company_id', $company->id)
                ->latest()
                ->limit(5)
                ->get(),
            'pendingChangeRequest' => SubscriptionChangeRequest::query()
                ->where('company_id', $company->id)
                ->where('status', 'pending')
                ->latest()
                ->first(),
            'companies' => auth()->user()->hasRole('super_admin')
                ? Company::query()
                    ->with(['subscriptions' => fn ($query) => $query->with('plan')->latest('created_at')->latest('id')])
                    ->orderBy('name')
                    ->get()
                : collect(),
            'pendingGlobalRequests' => auth()->user()->hasRole('super_admin')
                ? SubscriptionChangeRequest::query()
                    ->with(['company', 'currentPlan', 'requestedPlan', 'requester'])
                    ->where('status', 'pending')
                    ->latest()
                    ->get()
                : collect(),
        ]);
    }

    public function requestPlanChange(Request $request, TenantContext $tenantContext)
    {
        $company = $tenantContext->company();

        abort_if($company === null, 403);
        abort_unless($request->user()?->can('admin.access'), 403);

        $validated = $request->validate([
            'requested_plan_id' => ['required', 'exists:plans,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $currentSubscription = CompanySubscription::query()
            ->where('company_id', $company->id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $changeRequest = SubscriptionChangeRequest::query()->create([
            'company_id' => $company->id,
            'requested_by' => $request->user()?->id,
            'current_plan_id' => $currentSubscription?->plan_id,
            'requested_plan_id' => (int) $validated['requested_plan_id'],
            'status' => 'pending',
            'message' => $validated['message'] ?? null,
            'metadata' => ['source' => 'billing_panel'],
        ]);

        $this->auditChangeRequest($changeRequest, null, $request);

        return redirect()->route('billing.index')->with('status', 'Solicitud de cambio de plan enviada.');
    }

    public function resolvePlanChange(Request $request, SubscriptionChangeRequest $changeRequest)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);
        abort_unless($changeRequest->status === 'pending', 422);

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $oldValues = $changeRequest->only(['status', 'admin_notes', 'resolved_by', 'resolved_at']);

        $changeRequest->update([
            'status' => $validated['decision'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'resolved_by' => $request->user()?->id,
            'resolved_at' => now(),
        ]);

        if ($validated['decision'] === 'approved' && $changeRequest->requested_plan_id !== null) {
            $this->applyApprovedPlanChange($changeRequest, $request);
        }

        $this->auditChangeRequest($changeRequest->fresh(), $oldValues, $request);

        return redirect()->route('billing.index')->with('status', 'Solicitud de cambio de plan actualizada.');
    }

    public function updateSubscription(Request $request)
    {
        abort_unless($request->user()?->hasRole('super_admin'), 403);

        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['trialing', 'active', 'past_due', 'expired', 'cancelled'])],
            'trial_ends_at' => ['nullable', 'date'],
            'current_period_ends_at' => ['nullable', 'date'],
        ]);

        $subscription = CompanySubscription::query()
            ->where('company_id', $validated['company_id'])
            ->latest('created_at')
            ->latest('id')
            ->first();

        $oldValues = $subscription?->only([
            'company_id',
            'plan_id',
            'status',
            'trial_ends_at',
            'current_period_ends_at',
            'cancelled_at',
        ]);

        $values = [
            'company_id' => $validated['company_id'],
            'plan_id' => (int) $validated['plan_id'],
            'status' => $validated['status'],
            'trial_ends_at' => $validated['trial_ends_at'] ?? null,
            'current_period_ends_at' => $validated['current_period_ends_at'] ?? null,
            'cancelled_at' => $validated['status'] === 'cancelled' ? now() : null,
            'metadata' => array_filter([
                'source' => 'manual_admin',
                'updated_by' => $request->user()?->email,
            ]),
        ];

        if ($subscription === null) {
            $subscription = CompanySubscription::query()->create([
                ...$values,
                'trial_started_at' => $validated['status'] === 'trialing' ? now() : null,
                'current_period_started_at' => now(),
            ]);
        } else {
            $subscription->update($values);
        }

        $this->auditSubscriptionChange($subscription->fresh(), $oldValues, $request);

        return redirect()->route('billing.index')->with('status', 'Suscripcion actualizada.');
    }

    private function auditSubscriptionChange(CompanySubscription $subscription, ?array $oldValues, Request $request): void
    {
        DB::table('audit_logs')->insert([
            'company_id' => $subscription->company_id,
            'branch_id' => null,
            'user_id' => $request->user()?->id,
            'action' => $oldValues === null ? 'create' : 'update',
            'module' => 'billing',
            'target_type' => CompanySubscription::class,
            'target_id' => (string) $subscription->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values_json' => $oldValues === null ? null : json_encode($oldValues),
            'new_values_json' => json_encode($subscription->only([
                'company_id',
                'plan_id',
                'status',
                'trial_ends_at',
                'current_period_ends_at',
                'cancelled_at',
            ])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function applyApprovedPlanChange(SubscriptionChangeRequest $changeRequest, Request $request): void
    {
        $subscription = CompanySubscription::query()
            ->where('company_id', $changeRequest->company_id)
            ->latest('created_at')
            ->latest('id')
            ->first();

        $oldValues = $subscription?->only([
            'company_id',
            'plan_id',
            'status',
            'trial_ends_at',
            'current_period_ends_at',
            'cancelled_at',
        ]);

        $values = [
            'company_id' => $changeRequest->company_id,
            'plan_id' => $changeRequest->requested_plan_id,
            'status' => 'active',
            'trial_ends_at' => null,
            'current_period_started_at' => now(),
            'current_period_ends_at' => now()->addMonth(),
            'cancelled_at' => null,
            'metadata' => [
                'source' => 'subscription_change_request',
                'request_id' => $changeRequest->id,
                'updated_by' => $request->user()?->email,
            ],
        ];

        if ($subscription === null) {
            $subscription = CompanySubscription::query()->create($values);
        } else {
            $subscription->update($values);
        }

        $this->auditSubscriptionChange($subscription->fresh(), $oldValues, $request);
    }

    private function auditChangeRequest(SubscriptionChangeRequest $changeRequest, ?array $oldValues, Request $request): void
    {
        DB::table('audit_logs')->insert([
            'company_id' => $changeRequest->company_id,
            'branch_id' => null,
            'user_id' => $request->user()?->id,
            'action' => $oldValues === null ? 'create' : 'update',
            'module' => 'billing',
            'target_type' => SubscriptionChangeRequest::class,
            'target_id' => (string) $changeRequest->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values_json' => $oldValues === null ? null : json_encode($oldValues),
            'new_values_json' => json_encode($changeRequest->only([
                'company_id',
                'requested_by',
                'current_plan_id',
                'requested_plan_id',
                'status',
                'message',
                'admin_notes',
                'resolved_by',
                'resolved_at',
            ])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function commercialPlans()
    {
        return Plan::query()
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Plan $plan): int => (int) data_get($plan->metadata, 'display_order', 999))
            ->values();
    }
}
