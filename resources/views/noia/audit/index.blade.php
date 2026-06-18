<x-layouts.noia title="Auditoría" header="Auditoría">
    @php
        $moduleLabels = [
            'contacts' => 'Contactos',
            'messages' => 'Mensajes',
            'webhooks' => 'Webhooks',
            'consents' => 'Consentimientos',
            'conversations' => 'Conversaciones',
            'audit' => 'Auditoría',
            'settings' => 'Configuración',
        ];
        $actionLabels = [
            'create' => 'Crear',
            'update' => 'Actualizar',
            'delete' => 'Eliminar',
            'send' => 'Enviar',
            'block' => 'Bloquear',
            'revoke_consent' => 'Revocar consentimiento',
            'import' => 'Importar',
            'retry' => 'Reintentar',
            'webhook_processed' => 'Webhook procesado',
        ];
        $targetTypeLabels = [
            'Contact' => 'Contacto',
            'Message' => 'Mensaje',
            'InboundMessage' => 'Mensaje entrante',
            'ContactConsent' => 'Consentimiento',
            'Conversation' => 'Conversación',
            'AuditLog' => 'Auditoría',
        ];
        $hasFilters = filled(request('module'))
            || filled(request('action'))
            || filled(request('target_type'))
            || filled(request('user_id'))
            || filled(request('branch_id'))
            || filled(request('date_from'))
            || filled(request('date_to'));
    @endphp

    <div class="noia-card mb-6">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-500">Trazabilidad</p>
                <h3 class="mt-2 text-xl font-semibold text-slate-900">Registro de auditoría</h3>
                <p class="mt-1 max-w-2xl text-sm text-slate-600">
                    Consulta acciones sensibles, revisa cambios por módulo y navega el historial sin perder espacio de lectura.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 ring-1 ring-slate-200">
                    Total: {{ $logs->total() }} registros
                </span>
                @if($hasFilters)
                    <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-200">
                        Filtros activos
                    </span>
                @endif
                <button
                    type="button"
                    x-data
                    x-on:click="$dispatch('open-modal', 'audit-filters')"
                    class="noia-btn-primary"
                >
                    Abrir filtros
                </button>
                <a href="{{ route('reports.exports.audit-logs', request()->query()) }}" class="noia-btn-secondary">
                    Exportar CSV
                </a>
                @if($hasFilters)
                    <a href="{{ route('audit-logs.index') }}" class="noia-btn-secondary">
                        Limpiar
                    </a>
                @endif
            </div>
        </div>

        @if($hasFilters)
            <div class="mt-5 flex flex-wrap gap-2">
                @if(filled(request('module')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">Módulo: {{ request('module') }}</span>
                @endif
                @if(filled(request('action')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">Acción: {{ request('action') }}</span>
                @endif
                @if(filled(request('target_type')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">Objetivo: {{ request('target_type') }}</span>
                @endif
                @if(filled(request('user_id')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">
                        Usuario:
                        {{ optional($users->firstWhere('id', (int) request('user_id')))->name ?? request('user_id') }}
                    </span>
                @endif
                @if(filled(request('branch_id')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">
                        Sede:
                        {{ optional(($branches ?? collect())->firstWhere('id', request('branch_id')))->name ?? request('branch_id') }}
                    </span>
                @endif
                @if(filled(request('date_from')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">Desde: {{ request('date_from') }}</span>
                @endif
                @if(filled(request('date_to')))
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700 ring-1 ring-slate-200">Hasta: {{ request('date_to') }}</span>
                @endif
            </div>
        @endif
    </div>

    <div class="mb-4 grid gap-3 md:grid-cols-4">
        @foreach($summary->take(4) as $row)
            <div class="noia-card p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $moduleLabels[$row->module] ?? $row->module }}</p>
                <p class="mt-2 text-2xl font-semibold">{{ $row->total }}</p>
            </div>
        @endforeach
    </div>

    <div class="noia-table-wrap">
        <table class="noia-table">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Módulo</th>
                    <th class="px-4 py-3">Acción</th>
                    <th class="px-4 py-3">Objetivo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'Sistema' }}</td>
                        <td class="px-4 py-3">{{ $moduleLabels[$log->module] ?? $log->module }}</td>
                        <td class="px-4 py-3">{{ $actionLabels[$log->action] ?? $log->action }}</td>
                        <td class="px-4 py-3">
                            <a class="noia-link" href="{{ route('audit-logs.show', $log) }}">
                                {{ $targetTypeLabels[class_basename((string) $log->target_type)] ?? class_basename((string) $log->target_type) }} #{{ $log->target_id }}
                            </a>
                            <p class="mt-1 text-xs text-slate-500">Ver detalle de cambios</p>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $logs->appends(request()->query())->links() }}</div>

    <x-modal name="audit-filters" max-width="2xl" focusable>
        <div class="relative overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.18)]">
            <button
                type="button"
                x-data
                x-on:click="$dispatch('close-modal', 'audit-filters')"
                class="absolute right-6 top-6 z-20 flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
                aria-label="Cerrar filtros"
            >
                <span class="text-2xl leading-none">&times;</span>
            </button>

            <div class="border-b border-slate-200 bg-[linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] px-6 py-6">
                <div class="pr-16">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-500">Refinar búsqueda</p>
                        <h3 class="mt-2 text-[34px] font-semibold leading-none text-slate-950">Filtros de auditoría</h3>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-slate-600">
                            Usa combinaciones de módulo, acción y fechas para localizar cambios críticos con más rapidez.
                        </p>
                    </div>
                </div>
            </div>

            <form method="GET" class="space-y-6 px-6 py-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Módulo</span>
                        <input class="noia-input" name="module" value="{{ request('module') }}" placeholder="Ej. contacts">
                    </label>

                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Acción</span>
                        <input class="noia-input" name="action" value="{{ request('action') }}" placeholder="Ej. update">
                    </label>

                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Tipo objetivo</span>
                        <input class="noia-input" name="target_type" value="{{ request('target_type') }}" placeholder="Ej. Message">
                    </label>

                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Usuario</span>
                        <select class="noia-select" name="user_id">
                            <option value="">Todos los usuarios</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if(($branches ?? collect())->isNotEmpty())
                        <label>
                            <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Sede</span>
                            <select class="noia-select" name="branch_id">
                                <option value="">Todas las sedes</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Desde</span>
                        <input class="noia-input" type="date" name="date_from" value="{{ request('date_from') }}">
                    </label>

                    <label>
                        <span class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Hasta</span>
                        <input class="noia-input" type="date" name="date_to" value="{{ request('date_to') }}">
                    </label>
                </div>

                <div class="flex flex-col gap-4 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500 ring-1 ring-slate-200">
                        {{ $hasFilters ? 'Los filtros actuales se aplicarán al guardar cambios.' : 'Puedes combinar campos o usar solo uno.' }}
                    </div>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if($hasFilters)
                            <a href="{{ route('audit-logs.index') }}" class="noia-btn-secondary">
                                Limpiar
                            </a>
                        @endif
                        <button class="noia-btn-primary min-w-44">
                            Aplicar filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-modal>
</x-layouts.noia>
