<?php

namespace App\Modules\Billing\Presentation\Middleware;

use App\Modules\Billing\Application\Services\SubscriptionFeatureService;
use App\Modules\Tenancy\Application\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(
        private readonly SubscriptionFeatureService $features,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function handle(Request $request, Closure $next, string $featureCode): Response
    {
        $user = $request->user();

        if ($user?->hasRole('super_admin')) {
            return $next($request);
        }

        $companyId = $this->tenantContext->companyId();
        $subscription = $this->features->subscription($companyId);

        abort_if(
            $companyId === null || $subscription === null,
            403,
            'Esta empresa no tiene una suscripcion activa.'
        );

        abort_if(
            $subscription->isExpired(),
            403,
            'El periodo de prueba vencio. Renueva o cambia de plan para continuar operando.'
        );

        abort_if(
            ! $this->features->allows($companyId, $featureCode),
            403,
            'Tu plan actual no incluye esta funcionalidad.'
        );

        return $next($request);
    }
}
