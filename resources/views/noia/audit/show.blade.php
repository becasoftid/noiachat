<x-layouts.noia title="Detalle auditoría" header="Detalle de auditoría">
    @php
        $moduleLabels = [
            'contacts' => 'Contactos',
            'messages' => 'Mensajes',
            'webhooks' => 'Webhooks',
            'consents' => 'Consentimientos',
            'conversations' => 'Conversaciones',
            'audit' => 'Auditoría',
            'settings' => 'Configuración',
            'tenancy' => 'Empresa',
            'users' => 'Usuarios',
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
        $formatValue = function ($value): string {
            if (is_bool($value)) {
                return $value ? 'Sí' : 'No';
            }

            if ($value === null || $value === '') {
                return 'Sin valor';
            }

            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Sin valor';
            }

            return (string) $value;
        };
    @endphp

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('audit-logs.index') }}" class="noia-btn-secondary">Volver</a>
        <span class="noia-badge-neutral">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[.8fr_1.2fr]">
        <section class="noia-card h-fit">
            <h3 class="text-lg font-semibold text-slate-950">Contexto</h3>
            <dl class="mt-4 space-y-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Usuario</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistema' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Empresa / sede</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        {{ $log->company?->name ?? 'Sin empresa' }}{{ $log->branch ? ' / '.$log->branch->name : '' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Módulo y acción</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        {{ $moduleLabels[$log->module] ?? $log->module }} · {{ $actionLabels[$log->action] ?? $log->action }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Objetivo</dt>
                    <dd class="mt-1 break-all font-medium text-slate-900">{{ class_basename((string) $log->target_type) }} #{{ $log->target_id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Origen</dt>
                    <dd class="mt-1 break-words text-slate-700">
                        {{ $log->ip_address ?? 'Sin IP' }}
                        @if($log->user_agent)
                            <span class="mt-1 block text-xs text-slate-500">{{ $log->user_agent }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <section class="noia-card">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-semibold text-slate-950">Cambios old/new</h3>
                    <p class="mt-1 text-sm text-slate-500">Comparación de valores previos y nuevos registrados en auditoría.</p>
                </div>
                <span class="noia-badge-neutral">{{ $changes->where('changed', true)->count() }} cambios</span>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="noia-table">
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Antes</th>
                            <th>Después</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changes as $change)
                            <tr @class(['bg-amber-50/50' => $change['changed']])>
                                <td class="font-semibold text-slate-900">{{ $change['field'] }}</td>
                                <td class="max-w-sm break-words text-slate-700">{{ $formatValue($change['old']) }}</td>
                                <td class="max-w-sm break-words text-slate-700">{{ $formatValue($change['new']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-slate-500">Este registro no tiene valores old/new.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <details class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer text-sm font-semibold text-slate-700">Ver JSON técnico</summary>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Old</p>
                        <pre class="max-h-96 overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->old_values_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">New</p>
                        <pre class="max-h-96 overflow-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{{ json_encode($log->new_values_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </details>
        </section>
    </div>
</x-layouts.noia>
