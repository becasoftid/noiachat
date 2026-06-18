<x-layouts.noia title="Salud operativa" header="Salud operativa">
    @php
        $statusLabels = [
            'ok' => 'OK',
            'warning' => 'Alerta',
            'critical' => 'Critico',
        ];
        $statusClasses = [
            'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
            'critical' => 'border-rose-200 bg-rose-50 text-rose-800',
        ];
    @endphp

    <section class="noia-card mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Monitoreo</p>
                <h3 class="mt-2 text-xl font-semibold text-slate-950">Estado general: {{ $statusLabels[$status] ?? $status }}</h3>
                <p class="mt-1 text-sm text-slate-600">Cola, jobs fallidos, disco, webhook, backups y errores recientes.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="noia-badge-success">{{ $ok_count }} OK</span>
                <span class="noia-badge border-amber-200 bg-amber-50 text-amber-700">{{ $warning_count }} alertas</span>
                <span class="noia-badge-danger">{{ $critical_count }} criticos</span>
            </div>
        </div>
    </section>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach($checks as $check)
            <article class="rounded-lg border p-5 shadow-[0_18px_50px_rgba(15,23,42,0.06)] {{ $statusClasses[$check['status']] ?? 'border-slate-200 bg-white text-slate-800' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] opacity-75">{{ $statusLabels[$check['status']] ?? $check['status'] }}</p>
                        <h4 class="mt-2 text-lg font-semibold">{{ $check['label'] }}</h4>
                    </div>
                    <span class="rounded-lg bg-white/70 px-3 py-1 text-sm font-semibold text-slate-800 ring-1 ring-black/5">{{ $check['value'] }}</span>
                </div>
                <p class="mt-4 text-sm leading-6">{{ $check['detail'] }}</p>
                <p class="mt-3 rounded-lg bg-white/60 px-3 py-2 text-sm text-slate-700 ring-1 ring-black/5">{{ $check['action'] }}</p>
            </article>
        @endforeach
    </div>

    <section class="noia-card mt-6">
        <h3 class="text-lg font-semibold text-slate-950">Comando productivo</h3>
        <p class="mt-2 text-sm text-slate-600">El mismo criterio puede ejecutarse por consola o cron.</p>
        <pre class="mt-4 overflow-auto rounded-lg bg-slate-950 p-4 text-sm text-slate-100">php artisan noiachat:health-check</pre>
    </section>
</x-layouts.noia>
