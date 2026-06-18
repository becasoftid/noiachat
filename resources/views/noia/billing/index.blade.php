<x-layouts.noia title="Plan" header="Plan y suscripcion">
    @php
        $subscription = $overview['subscription'];
        $plan = $overview['plan'];
        $formatLimit = fn ($value) => $value === null ? 'Ilimitado' : number_format($value, 0, ',', '.');
        $formatPrice = fn ($availablePlan) => $availablePlan->price_cents > 0
            ? '$'.number_format($availablePlan->price_cents / 100, 0, ',', '.').' '.$availablePlan->currency
            : data_get($availablePlan->metadata, 'price_note', 'Tarifa por definir');
        $badgeClass = match ($overview['status_tone']) {
            'success' => 'noia-badge-success',
            'danger' => 'noia-badge-danger',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'noia-badge-neutral',
        };
    @endphp

    <div class="space-y-6">
        <section class="noia-card">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">{{ $company->name }}</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-950">{{ $plan?->name ?? 'Sin plan asignado' }}</h3>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">{{ $plan?->description ?? 'Asigna un plan para habilitar funcionalidades y limites comerciales.' }}</p>
                </div>
                <span class="noia-badge {{ $badgeClass }}">{{ $overview['status_label'] }}</span>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Periodo</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $plan?->billing_period ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Dias de prueba</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $overview['remaining_trial_days'] ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Fin de prueba</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $subscription?->trial_ends_at?->format('Y-m-d') ?? '-' }}</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Fin de periodo</p>
                    <p class="mt-2 text-lg font-semibold text-slate-950">{{ $subscription?->current_period_ends_at?->format('Y-m-d') ?? '-' }}</p>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
            <div class="noia-card">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Limites del plan</h3>
                        <p class="mt-1 text-sm text-slate-500">Consumo actual frente a los limites comerciales de la empresa.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach($overview['limits'] as $limit)
                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-slate-900">{{ $limit['label'] }}</p>
                                <p class="text-sm font-semibold text-slate-600">
                                    {{ $limit['used'] }} / {{ $limit['max'] ?? 'Ilimitado' }}
                                </p>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full {{ $limit['max'] !== null && $limit['percent'] >= 90 ? 'bg-amber-500' : 'bg-cyan-700' }}" style="width: {{ $limit['max'] === null ? 100 : $limit['percent'] }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                @if($limit['max'] === null)
                                    Sin limite configurado.
                                @else
                                    Disponibles: {{ $limit['available'] }}.
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="noia-card">
                <h3 class="text-lg font-semibold text-slate-950">Accion comercial</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">Solicita un cambio de plan para que el equipo comercial lo revise y lo active desde el panel.</p>

                @if($pendingChangeRequest)
                    <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Solicitud pendiente para {{ $pendingChangeRequest->requestedPlan?->name ?? 'plan solicitado' }}.
                    </div>
                @else
                    <form method="POST" action="{{ route('billing.change-requests.store') }}" class="mt-5 space-y-3">
                        @csrf
                        <select class="noia-select" name="requested_plan_id" required>
                            @foreach($plans->where('code', '!=', 'basic_trial') as $availablePlan)
                                <option value="{{ $availablePlan->id }}" @selected($plan?->id === $availablePlan->id)>{{ $availablePlan->name }} · {{ $formatPrice($availablePlan) }}</option>
                            @endforeach
                        </select>
                        <textarea class="noia-textarea min-h-[96px]" name="message" rows="3" placeholder="Cuéntanos qué necesita la empresa: más usuarios, sedes, contactos, reportes o soporte.">{{ old('message') }}</textarea>
                        <button class="noia-btn-primary w-full">Solicitar cambio de plan</button>
                    </form>
                @endif
            </div>
        </section>

        <section class="noia-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Catalogo comercial</h3>
                    <p class="mt-1 text-sm text-slate-500">Comparacion rapida de planes disponibles para la empresa.</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                @foreach($plans as $availablePlan)
                    <div class="rounded-lg border p-4 {{ data_get($availablePlan->metadata, 'highlight') ? 'border-cyan-300 bg-cyan-50/60' : 'border-slate-200 bg-white' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-950">{{ $availablePlan->name }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">{{ data_get($availablePlan->metadata, 'commercial_label', $availablePlan->billing_period) }}</p>
                            </div>
                            @if($plan?->id === $availablePlan->id)
                                <span class="noia-badge noia-badge-success">Actual</span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-900">{{ $formatPrice($availablePlan) }}</p>
                        <p class="mt-2 min-h-[60px] text-sm leading-5 text-slate-500">{{ data_get($availablePlan->metadata, 'audience', $availablePlan->description) }}</p>
                        <dl class="mt-4 grid gap-2 text-sm text-slate-600">
                            <div class="flex justify-between gap-3"><dt>Usuarios</dt><dd class="font-semibold">{{ $formatLimit($availablePlan->max_users) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt>Sedes</dt><dd class="font-semibold">{{ $formatLimit($availablePlan->max_branches) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt>Contactos</dt><dd class="font-semibold">{{ $formatLimit($availablePlan->max_contacts) }}</dd></div>
                            <div class="flex justify-between gap-3"><dt>Canales</dt><dd class="font-semibold">{{ $formatLimit($availablePlan->max_whatsapp_channels) }}</dd></div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="noia-card">
            <h3 class="text-lg font-semibold text-slate-950">Solicitudes recientes</h3>
            <div class="mt-5 overflow-x-auto rounded-lg border border-slate-200">
                <table class="noia-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Plan actual</th>
                            <th>Plan solicitado</th>
                            <th>Estado</th>
                            <th>Solicitante</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeRequests as $changeRequest)
                            <tr>
                                <td>{{ $changeRequest->created_at->format('Y-m-d H:i') }}</td>
                                <td>{{ $changeRequest->currentPlan?->name ?? '-' }}</td>
                                <td>{{ $changeRequest->requestedPlan?->name ?? '-' }}</td>
                                <td>
                                    <span class="noia-badge {{ $changeRequest->status === 'pending' ? 'border-amber-200 bg-amber-50 text-amber-700' : ($changeRequest->status === 'approved' ? 'noia-badge-success' : 'noia-badge-neutral') }}">
                                        {{ $changeRequest->status }}
                                    </span>
                                </td>
                                <td>{{ $changeRequest->requester?->email ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-slate-500">No hay solicitudes registradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="noia-card">
            <h3 class="text-lg font-semibold text-slate-950">Funcionalidades incluidas</h3>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($overview['features'] as $feature)
                    <div class="rounded-lg border border-slate-200 bg-slate-50/80 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $feature['name'] }}</p>
                                <p class="mt-1 text-xs font-medium uppercase tracking-[0.14em] text-slate-500">{{ $feature['module'] }}</p>
                            </div>
                            <span class="noia-badge {{ $feature['enabled'] ? 'noia-badge-success' : 'noia-badge-neutral' }}">
                                {{ $feature['enabled'] ? 'Incluida' : 'No incluida' }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">{{ $feature['code'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No hay funcionalidades asociadas al plan actual.</p>
                @endforelse
            </div>
        </section>

        @if(auth()->user()->hasRole('super_admin'))
            <section class="noia-card">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Solicitudes pendientes</h3>
                        <p class="mt-1 text-sm text-slate-500">Aprueba o rechaza cambios pedidos por administradores de empresa.</p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($pendingGlobalRequests as $changeRequest)
                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-950">{{ $changeRequest->company?->name }}</p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ $changeRequest->currentPlan?->name ?? '-' }} -> {{ $changeRequest->requestedPlan?->name ?? '-' }} · {{ $changeRequest->requester?->email ?? 'sin solicitante' }}
                                    </p>
                                    @if($changeRequest->message)
                                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ $changeRequest->message }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('billing.change-requests.resolve', $changeRequest) }}" class="grid gap-2 sm:min-w-[360px]">
                                    @csrf
                                    @method('PATCH')
                                    <textarea class="noia-textarea min-h-[84px]" name="admin_notes" rows="2" placeholder="Nota interna opcional"></textarea>
                                    <div class="flex gap-2">
                                        <button name="decision" value="approved" class="noia-btn-success flex-1">Aprobar</button>
                                        <button name="decision" value="rejected" class="noia-btn-secondary flex-1">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No hay solicitudes pendientes.</p>
                    @endforelse
                </div>
            </section>

            <section class="noia-card">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Administracion global de suscripciones</h3>
                        <p class="mt-1 text-sm text-slate-500">Cambia plan, estado y fechas sin tocar base de datos.</p>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="noia-table">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Plan</th>
                                <th>Estado</th>
                                <th>Fin prueba</th>
                                <th>Fin periodo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($companies as $managedCompany)
                                @php($activeSubscription = $managedCompany->subscriptions->first())
                                <tr>
                                    <td class="min-w-56">
                                        <input form="subscription-{{ $managedCompany->id }}" type="hidden" name="company_id" value="{{ $managedCompany->id }}">
                                        <p class="font-semibold text-slate-900">{{ $managedCompany->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $managedCompany->slug }}</p>
                                    </td>
                                    <td>
                                        <select form="subscription-{{ $managedCompany->id }}" class="noia-select min-w-48" name="plan_id" required>
                                            @foreach($plans as $availablePlan)
                                                <option value="{{ $availablePlan->id }}" @selected(($activeSubscription?->plan_id ?? $plan?->id) === $availablePlan->id)>{{ $availablePlan->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select form="subscription-{{ $managedCompany->id }}" class="noia-select min-w-40" name="status" required>
                                            @foreach(['trialing' => 'Prueba', 'active' => 'Activo', 'past_due' => 'Pago pendiente', 'expired' => 'Vencido', 'cancelled' => 'Cancelado'] as $status => $label)
                                                <option value="{{ $status }}" @selected(($activeSubscription?->status ?? 'trialing') === $status)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input form="subscription-{{ $managedCompany->id }}" class="noia-input min-w-40" type="date" name="trial_ends_at" value="{{ $activeSubscription?->trial_ends_at?->format('Y-m-d') }}">
                                    </td>
                                    <td>
                                        <input form="subscription-{{ $managedCompany->id }}" class="noia-input min-w-40" type="date" name="current_period_ends_at" value="{{ $activeSubscription?->current_period_ends_at?->format('Y-m-d') }}">
                                    </td>
                                    <td class="text-right">
                                        <form id="subscription-{{ $managedCompany->id }}" method="POST" action="{{ route('billing.subscription.update') }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="noia-btn-secondary">Guardar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-layouts.noia>
