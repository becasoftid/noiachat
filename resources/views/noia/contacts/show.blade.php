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
    @endphp
    <div class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
        <div class="noia-card">
            <h3 class="text-lg font-semibold">{{ $contact->full_name }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ $contact->primary_phone }} · {{ $contact->email }}</p>
            <p class="mt-2 text-sm">Estado: <strong>{{ $contactStatusLabels[$contact->status] ?? $contact->status }}</strong></p>
            <div class="mt-4"><a class="noia-btn-primary text-sm" href="{{ route('contacts.edit', $contact) }}">Editar</a></div>
        </div>
        <div class="space-y-6">
            <div class="noia-card">
                <h3 class="font-semibold">Consentimientos</h3>
                <form method="POST" action="{{ route('contacts.consents.store', $contact) }}" class="mt-4 grid gap-3 md:grid-cols-3">
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
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach($contact->contactConsents as $consent)
                        <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span>{{ $consentStatusLabels[$consent->status] ?? $consent->status }} · {{ $consentSourceLabels[$consent->source] ?? $consent->source }} · {{ optional($consent->created_at)->format('Y-m-d H:i') }}</span>
                            @if($consent->status === 'granted')
                                <form method="POST" action="{{ route('contacts.consents.revoke', $contact) }}">
                                    @csrf
                                    <input type="hidden" name="channel_id" value="{{ $consent->channel_id }}">
                                    <button class="text-sm text-rose-700">Revocar</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="noia-card">
                <h3 class="font-semibold">Lista de exclusión</h3>
                <form method="POST" action="{{ route('contacts.blacklist.store', $contact) }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
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
                    @foreach($contact->contactBlacklist as $entry)
                        <li class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2">
                            <span>{{ $blacklistReasonLabels[$entry->reason] ?? $entry->reason }} · {{ optional($entry->created_at)->format('Y-m-d H:i') }}</span>
                            <form method="POST" action="{{ route('contacts.blacklist.destroy', [$contact, $entry]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm text-sky-700">Quitar</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="noia-card"><h3 class="font-semibold">Mensajes</h3><ul class="mt-3 space-y-2 text-sm">@foreach($contact->messages as $message)<li><a class="text-sky-700" href="{{ route('messages.show', $message) }}">{{ $messageTypeLabels[$message->type] ?? $message->type }} · {{ $messageStatusLabels[$message->status] ?? $message->status }}</a></li>@endforeach</ul></div>
        </div>
    </div>
</x-layouts.noia>
