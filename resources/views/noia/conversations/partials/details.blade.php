@php
    $displayTimezone = config('app.display_timezone', 'America/Bogota');
    $contactName = $conversation->contact->full_name;
    $contactInitials = collect(explode(' ', trim($contactName)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->join('');
    $lastActivity = $conversation->last_message_at?->copy()->timezone($displayTimezone);
    $windowUntil = $customerCareWindowUntil?->copy()->timezone($displayTimezone);
@endphp

<div class="space-y-5">
    <section class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
            <h3 class="font-semibold text-slate-950">Detalles del contacto</h3>
            <span class="text-slate-400" aria-hidden="true">⌃</span>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white">
                {{ $contactInitials ?: 'N' }}
            </div>
            <div class="min-w-0">
                <p class="truncate font-semibold text-slate-950">{{ $contactName }}</p>
                <p class="text-sm text-slate-500">{{ $conversation->contact->primary_phone }}</p>
            </div>
        </div>
        <div class="mt-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">
            {{ $conversation->channel?->name ?? 'WhatsApp' }}
        </div>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="font-semibold text-slate-950">Información</h3>
        <dl class="mt-4 space-y-4 text-sm">
            <div>
                <dt class="font-semibold text-slate-500">Creado</dt>
                <dd class="mt-1 text-slate-800">{{ $conversation->created_at?->copy()->timezone($displayTimezone)->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">Responsable</dt>
                <dd class="mt-1 text-slate-800">{{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</dd>
            </div>
            <div>
                <dt class="font-semibold text-slate-500">Última actividad</dt>
                <dd class="mt-1 text-slate-800">{{ $lastActivity ? $lastActivity->format('d/m/Y H:i') : 'Sin actividad' }}</dd>
            </div>
        </dl>
    </section>

    <section class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="font-semibold text-slate-950">Estado del canal</h3>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">{{ $conversation->channel?->name ?? 'WhatsApp' }}</dt>
                <dd class="@class(['font-semibold', 'text-emerald-700' => $conversation->channel?->is_active, 'text-amber-700' => ! $conversation->channel?->is_active])">
                    {{ $conversation->channel?->is_active ? 'Conectado' : 'Revisar' }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Ventana de atención</dt>
                <dd class="@class(['rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-amber-100 text-amber-800' => $customerCareWindowClosed, 'bg-emerald-100 text-emerald-700' => ! $customerCareWindowClosed])">
                    {{ $customerCareWindowClosed ? 'Cerrada' : 'Abierta' }}
                </dd>
            </div>
            <div class="flex items-center justify-between gap-3">
                <dt class="text-slate-500">Tiempo restante</dt>
                <dd class="font-semibold text-slate-700">
                    @if(! $customerCareWindowClosed && $windowUntil)
                        {{ now()->diffForHumans($windowUntil, ['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                    @else
                        0h 0m
                    @endif
                </dd>
            </div>
        </dl>
    </section>

    <a href="{{ route('contacts.show', $conversation->contact) }}" class="noia-btn-secondary w-full">Ver perfil completo</a>
</div>
