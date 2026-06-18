<x-layouts.noia title="Fallos recientes" header="Fallos recientes">
    @php
        $statusLabels = [
            'failed' => 'Fallido',
            'bounced' => 'Rebotado',
            'blocked_by_policy' => 'Bloqueado por política',
        ];
        $typeLabels = [
            'text' => 'Texto',
            'image' => 'Imagen',
            'document' => 'Documento',
            'template' => 'Plantilla',
        ];
    @endphp

    <form method="GET" class="noia-filter-bar mb-5 grid gap-3 md:grid-cols-[1fr_140px]">
        <input class="noia-input" name="search" value="{{ $search }}" placeholder="Buscar contacto, telefono, evento o error">
        <button class="noia-btn-primary">Filtrar</button>
    </form>

    <div class="grid gap-6 xl:grid-cols-[1.2fr_.8fr]">
        <div class="space-y-6">
            <section class="noia-card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Mensajes reintentables</h3>
                        <p class="mt-1 text-sm text-slate-500">Errores visibles para soporte con accion directa sobre el mensaje.</p>
                    </div>
                    <span class="noia-badge-danger">{{ $messages->total() }} fallos</span>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <table class="noia-table">
                        <thead>
                            <tr>
                                <th>Contacto</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th>Causa</th>
                                <th>Accion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($messages as $message)
                                @php
                                    $errorLog = $message->providerLogs->first(fn ($log) => $log->hasError());
                                    $errorText = $message->complianceBlockDescription()
                                        ?? $errorLog?->errorMessage()
                                        ?? $errorLog?->errorDetails()
                                        ?? 'Revisar detalle tecnico del mensaje.';
                                @endphp
                                <tr>
                                    <td>
                                        <a class="noia-link" href="{{ route('messages.show', $message) }}">{{ $message->contact->full_name }}</a>
                                        <p class="mt-1 text-xs text-slate-500">{{ $message->contact->primary_phone }}</p>
                                    </td>
                                    <td>{{ $typeLabels[$message->type] ?? $message->type }}</td>
                                    <td>
                                        <span class="noia-badge-danger">{{ $statusLabels[$message->status] ?? $message->status }}</span>
                                        @if($errorLog?->errorCode())
                                            <p class="mt-2 text-xs font-semibold text-slate-500">Codigo {{ $errorLog->errorCode() }}</p>
                                        @endif
                                    </td>
                                    <td class="max-w-md">
                                        <p class="text-sm text-slate-700">{{ $errorText }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Sugerencia: revisar causa, corregir dato/configuracion y reintentar si aplica.</p>
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-2">
                                            <a class="noia-btn-secondary h-10 px-3" href="{{ route('messages.show', $message) }}">Ver</a>
                                            @can('create', \App\Modules\Messaging\Infrastructure\Persistence\Models\Message::class)
                                                <form method="POST" action="{{ route('messages.retry', $message) }}">
                                                    @csrf
                                                    <button class="noia-btn-warning h-10 px-3">Reintentar</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-slate-500">Sin mensajes fallidos para el filtro actual.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">{{ $messages->appends(request()->except('messages_page'))->links() }}</div>
            </section>

            <section class="noia-card">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">Errores de proveedor</h3>
                        <p class="mt-1 text-sm text-slate-500">Eventos de Meta y fallos registrados por integracion.</p>
                    </div>
                    <span class="noia-badge-danger">{{ $providerLogs->total() }} eventos</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($providerLogs as $log)
                        <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $log->event_type }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $log->provider }} · {{ $log->direction }} · {{ $log->created_at->format('Y-m-d H:i:s') }}</p>
                                </div>
                                @if($log->message)
                                    <a class="noia-link text-sm" href="{{ route('messages.show', $log->message) }}">Ver mensaje</a>
                                @endif
                            </div>

                            <div class="mt-3 rounded-md border border-rose-200 bg-white px-3 py-2 text-sm text-rose-800">
                                @if($log->errorCode())
                                    <p class="font-semibold">Codigo {{ $log->errorCode() }}</p>
                                @endif
                                <p>{{ $log->errorMessage() ?? $log->errorDetails() ?? 'Evento de error sin descripcion del proveedor.' }}</p>
                                @if($log->errorDetails() && $log->errorDetails() !== $log->errorMessage())
                                    <p class="mt-1 text-rose-700">{{ $log->errorDetails() }}</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Sin errores de proveedor para el filtro actual.</p>
                    @endforelse
                </div>

                <div class="mt-4">{{ $providerLogs->appends(request()->except('logs_page'))->links() }}</div>
            </section>
        </div>

        <aside class="noia-card h-fit">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Jobs fallidos</h3>
                    <p class="mt-1 text-sm text-slate-500">Ultimos fallos de cola registrados por Laravel.</p>
                </div>
                <span class="noia-badge-neutral">{{ $failedJobs->count() }} recientes</span>
            </div>

            <div class="mt-4 space-y-3">
                @forelse($failedJobs as $job)
                    <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $job['name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $job['connection'] }} · {{ $job['queue'] }} · {{ \Illuminate\Support\Carbon::parse($job['failed_at'])->format('Y-m-d H:i:s') }}</p>
                            </div>
                            <span class="noia-badge-danger">Job</span>
                        </div>
                        <p class="mt-3 text-sm text-slate-700">{{ $job['error'] }}</p>
                        <p class="mt-2 text-xs text-slate-500">Sugerencia: corregir causa, revisar worker y usar comandos de cola para reintentar si corresponde.</p>
                    </article>
                @empty
                    <p class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Sin jobs fallidos registrados.</p>
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.noia>
