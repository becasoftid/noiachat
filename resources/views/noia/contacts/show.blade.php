<x-layouts.noia title="Detalle de Contacto" header="Detalle de contacto">
    @php
        $contactStatusLabels = [
            'active' => 'Activo',
            'blocked' => 'Bloqueado',
            'no_contact' => 'No contactar',
            'invalid' => 'Inválido',
        ];
        $consentStatusLabels = [
            'granted' => 'Otorgado',
            'revoked' => 'Revocado',
            'expired' => 'Expirado',
        ];
        $consentSourceLabels = [
            'manual' => 'Manual',
            'web' => 'Web',
            'contract' => 'Contrato',
            'call' => 'Llamada',
            'import' => 'Importación',
            'whatsapp' => 'WhatsApp',
        ];
        $blacklistReasonLabels = [
            'opt_out' => 'Solicitud de baja',
            'manual_opt_out' => 'Baja manual',
            'manual' => 'Manual',
        ];
        $messageTypeLabels = [
            'text' => 'Texto',
            'image' => 'Imagen',
            'document' => 'Documento',
            'template' => 'Plantilla',
        ];
        $messageStatusLabels = [
            'draft' => 'Borrador',
            'queued' => 'En cola',
            'sending' => 'Enviando',
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'read' => 'Leído',
            'failed' => 'Fallido',
            'bounced' => 'Rebotado',
            'cancelled' => 'Cancelado',
            'blocked_by_policy' => 'Bloqueado por política',
        ];
        $initials = collect(explode(' ', trim($contact->full_name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('');
        $grantedConsents = $contact->contactConsents->where('status', 'granted')->count();
    @endphp

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(320px,.7fr)_minmax(0,1fr)]">
        <div class="space-y-6 xl:sticky xl:top-6">
            <section class="noia-card">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[#10202a] text-xl font-bold text-white">
                        {{ $initials ?: 'N' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-xl font-semibold text-slate-950">{{ $contact->full_name }}</h3>
                            <span class="{{ $contact->status === 'active' ? 'noia-badge-success' : 'noia-badge-neutral' }}">
                                {{ $contactStatusLabels[$contact->status] ?? $contact->status }}
                            </span>
                        </div>
                        <p class="mt-2 break-all text-sm font-medium text-slate-600">{{ $contact->primary_phone }}</p>
                        @if(filled($contact->email))
                            <p class="mt-1 break-all text-sm text-slate-500">{{ $contact->email }}</p>
                        @endif
                    </div>
                </div>

                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Teléfono</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $contact->primary_phone }}</dd>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Creado</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ optional($contact->created_at)->format('Y-m-d H:i') ?? 'Sin fecha' }}</dd>
                    </div>
                </dl>

                <div class="mt-6 grid grid-cols-3 gap-3">
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-3">
                        <p class="text-xl font-bold text-emerald-800">{{ $grantedConsents }}</p>
                        <p class="mt-1 text-xs font-semibold text-emerald-700">Consent.</p>
                    </div>
                    <div class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-3">
                        <p class="text-xl font-bold text-rose-800">{{ $contact->contactBlacklist->count() }}</p>
                        <p class="mt-1 text-xs font-semibold text-rose-700">Bloqueos</p>
                    </div>
                    <div class="rounded-lg border border-cyan-100 bg-cyan-50 px-3 py-3">
                        <p class="text-xl font-bold text-cyan-800">{{ $contact->messages->count() }}</p>
                        <p class="mt-1 text-xs font-semibold text-cyan-700">Mensajes</p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a class="noia-btn-primary text-sm" href="{{ route('contacts.edit', $contact) }}">Editar contacto</a>
                    <a class="noia-btn-secondary text-sm" href="{{ route('contacts.index') }}">Volver</a>
                </div>
            </section>
        </div>

        <div class="space-y-6">
            @can('admin.access')
                <div class="noia-card">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-slate-950">Fusionar contacto</h3>
                            <p class="mt-1 text-sm text-slate-500">Une duplicados moviendo la información al contacto destino.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('contacts.merge', $contact) }}" class="mt-4 grid gap-3 lg:grid-cols-[1fr_auto]">
                        @csrf
                        <select class="noia-select" name="target_contact_id">
                            <option value="">Selecciona contacto destino</option>
                            @foreach($mergeCandidates as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->full_name }} · {{ $candidate->primary_phone }}</option>
                            @endforeach
                        </select>
                        <button class="noia-btn-warning">Fusionar en destino</button>
                    </form>
                </div>
            @endcan

            <div class="noia-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-950">Consentimientos</h3>
                        <p class="mt-1 text-sm text-slate-500">Controla los canales autorizados para contactar a esta persona.</p>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $grantedConsents }} activos</span>
                </div>
                <form method="POST" action="{{ route('contacts.consents.store', $contact) }}" class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_auto]">
                    @csrf
                    <select class="noia-select" name="channel_id">
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                    <select class="noia-select" name="source">
                        @foreach($consentSourceLabels as $source => $label)
                            <option value="{{ $source }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="noia-btn-success">Otorgar</button>
                </form>

                <div class="mt-4 space-y-3 text-sm">
                    @forelse($contact->contactConsents->sortByDesc('created_at') as $consent)
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="{{ $consent->status === 'granted' ? 'noia-badge-success' : 'noia-badge-neutral' }}">
                                            {{ $consentStatusLabels[$consent->status] ?? $consent->status }}
                                        </span>
                                        <span class="font-semibold text-slate-800">{{ $consent->channel?->name ?? 'Canal eliminado' }}</span>
                                    </div>
                                    <p class="mt-2 text-slate-600">Fuente: {{ $consentSourceLabels[$consent->source] ?? $consent->source }}</p>
                                </div>

                                @if($consent->status === 'granted')
                                    <form method="POST" action="{{ route('contacts.consents.revoke', $contact) }}">
                                        @csrf
                                        <input type="hidden" name="channel_id" value="{{ $consent->channel_id }}">
                                        <button class="text-sm font-semibold text-rose-700">Revocar</button>
                                    </form>
                                @endif
                            </div>

                            <dl class="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-2">
                                <div>
                                    <dt class="font-semibold text-slate-500">Registrado</dt>
                                    <dd>{{ optional($consent->created_at)->format('Y-m-d H:i') }}</dd>
                                </div>
                                @if($consent->granted_at)
                                    <div>
                                        <dt class="font-semibold text-slate-500">Otorgado</dt>
                                        <dd>{{ $consent->granted_at->format('Y-m-d H:i') }} · {{ $consent->grantedBy?->name ?? 'Sistema' }}</dd>
                                    </div>
                                @endif
                                @if($consent->revoked_at)
                                    <div>
                                        <dt class="font-semibold text-slate-500">Revocado</dt>
                                        <dd>{{ $consent->revoked_at->format('Y-m-d H:i') }} · {{ $consent->revokedBy?->name ?? 'Sistema' }}</dd>
                                    </div>
                                @endif
                                @if($consent->expires_at)
                                    <div>
                                        <dt class="font-semibold text-slate-500">Expira</dt>
                                        <dd>{{ $consent->expires_at->format('Y-m-d H:i') }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if(filled($consent->notes))
                                <p class="mt-3 rounded-md bg-white px-3 py-2 text-xs text-slate-600">{{ $consent->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-4 text-sm text-slate-500">
                            Sin historial de consentimiento.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="noia-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-950">Lista de exclusión</h3>
                        <p class="mt-1 text-sm text-slate-500">Bloquea el envío por canal cuando el contacto no debe recibir mensajes.</p>
                    </div>
                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">{{ $contact->contactBlacklist->count() }} registros</span>
                </div>
                <form method="POST" action="{{ route('contacts.blacklist.store', $contact) }}" class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_auto]">
                    @csrf
                    <select class="noia-select" name="channel_id">
                        @foreach($channels as $channel)
                            <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                        @endforeach
                    </select>
                    <input class="noia-input" name="reason" placeholder="Razón">
                    <button class="noia-btn-danger">Bloquear</button>
                </form>
                <ul class="mt-4 space-y-2 text-sm">
                    @forelse($contact->contactBlacklist as $entry)
                        <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span>{{ $blacklistReasonLabels[$entry->reason] ?? $entry->reason }} · {{ optional($entry->created_at)->format('Y-m-d H:i') }}</span>
                            <form method="POST" action="{{ route('contacts.blacklist.destroy', [$contact, $entry]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="noia-link text-sm">Quitar</button>
                            </form>
                        </li>
                    @empty
                        <li class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-4 text-sm text-slate-500">
                            Sin registros de exclusión.
                        </li>
                    @endforelse
                </ul>
            </div>

            <div class="noia-card">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-950">Mensajes</h3>
                        <p class="mt-1 text-sm text-slate-500">Últimos mensajes asociados al contacto.</p>
                    </div>
                    <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-700">{{ $contact->messages->count() }} total</span>
                </div>
                <ul class="mt-4 divide-y divide-slate-100 text-sm">
                    @forelse($contact->messages->sortByDesc('created_at')->take(8) as $message)
                        <li>
                            <a class="flex items-center justify-between gap-4 py-3 text-slate-700 transition hover:text-cyan-700" href="{{ route('messages.show', $message) }}">
                                <span class="font-semibold">{{ $messageTypeLabels[$message->type] ?? $message->type }}</span>
                                <span class="text-right text-xs font-semibold text-slate-500">{{ $messageStatusLabels[$message->status] ?? $message->status }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-4 text-sm text-slate-500">
                            Sin mensajes asociados.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.noia>
