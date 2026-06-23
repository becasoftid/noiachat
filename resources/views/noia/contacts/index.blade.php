<x-layouts.noia title="Contactos" header="Contactos">
    @php
        $statusLabels = [
            'active' => 'Activo',
            'blocked' => 'Bloqueado',
            'no_contact' => 'No contactar',
            'invalid' => 'Inválido',
        ];
    @endphp
    <div class="mb-4 flex items-center justify-between gap-4">
        <form method="GET" class="flex gap-2">
            <input class="noia-input" type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nombre, teléfono o email">
            <button class="noia-btn-primary">Buscar</button>
        </form>
        <div class="flex gap-2">
            <a href="{{ route('reports.exports.contacts', request()->query()) }}" class="noia-btn-secondary">Exportar CSV</a>
            <a href="{{ route('contacts.import.create') }}" class="noia-btn-secondary">Importar</a>
            <a href="{{ route('contacts.create') }}" class="noia-btn-success">Nuevo contacto</a>
        </div>
    </div>
    <div class="noia-table-wrap">
        <table class="noia-table">
            <thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Teléfono</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr></thead>
            <tbody>
                @foreach($contacts as $contact)
                    @php
                        $primaryContactChannel = $contact->contactChannels
                            ->first(fn ($contactChannel) => $contactChannel->is_active && $contactChannel->is_primary && $contactChannel->channel?->is_active);
                        $directChannelId = $primaryContactChannel?->channel_id
                            ?? ($activeChannels->count() === 1 ? $activeChannels->first()->id : null);
                    @endphp
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $contact->full_name }}</td>
                        <td class="px-4 py-3">{{ $contact->primary_phone }}</td>
                        <td class="px-4 py-3">{{ $statusLabels[$contact->status] ?? $contact->status }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <a
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-cyan-200 hover:text-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                    href="{{ route('contacts.show', $contact) }}"
                                    title="Ver contacto"
                                    aria-label="Ver contacto {{ $contact->full_name }}"
                                >
                                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </a>
                                @can('messages.send')
                                    @if($directChannelId)
                                        <form method="POST" action="{{ route('conversations.start') }}">
                                            @csrf
                                            <input type="hidden" name="contact_id" value="{{ $contact->id }}">
                                            <input type="hidden" name="channel_id" value="{{ $directChannelId }}">
                                            <button
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                                title="Enviar mensaje"
                                                aria-label="Enviar mensaje a {{ $contact->full_name }}"
                                            >
                                                <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" stroke-linecap="round" stroke-linejoin="round" />
                                                    <path d="m10 8 5 4-5 4V8Z" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        <a
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-100"
                                            href="{{ route('conversations.index', ['new' => 1, 'contact_id' => $contact->id]) }}"
                                            title="{{ $activeChannels->isEmpty() ? 'Configura un canal WhatsApp activo' : 'Enviar mensaje' }}"
                                            aria-label="Enviar mensaje a {{ $contact->full_name }}"
                                        >
                                            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="m10 8 5 4-5 4V8Z" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contacts->links() }}</div>
</x-layouts.noia>
