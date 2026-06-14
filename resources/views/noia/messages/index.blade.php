<x-layouts.noia title="Mensajes" header="Mensajes">
    @php
        $typeLabels = [
            'text' => 'Texto',
            'image' => 'Imagen',
            'document' => 'Documento',
            'template' => 'Plantilla',
        ];
        $statusLabels = [
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
    <div class="mb-4 flex items-center justify-between gap-4">
        <form method="GET" class="noia-filter-bar grid flex-1 gap-3 md:grid-cols-6">
            <input class="noia-input" name="search" value="{{ request('search') }}" placeholder="Buscar contacto o teléfono">
            <select class="noia-select" name="type">
                <option value="">Tipo</option>
                @foreach($typeLabels as $type => $label)
                    <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="noia-select" name="status">
                <option value="">Estado</option>
                @foreach($statusLabels as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="noia-input" type="date" name="date_from" value="{{ request('date_from') }}">
            <input class="noia-input" type="date" name="date_to" value="{{ request('date_to') }}">
            <button class="noia-btn-primary">Filtrar</button>
        </form>
        <a href="{{ route('messages.create') }}" class="noia-btn-primary">Nuevo envío</a>
    </div>
    <div class="noia-table-wrap">
        <table class="noia-table">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3">Contacto</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @foreach($messages as $message)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3"><a class="noia-link" href="{{ route('messages.show', $message) }}">{{ $message->contact->full_name }}</a></td>
                        <td class="px-4 py-3">{{ $typeLabels[$message->type] ?? $message->type }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $statusLabels[$message->status] ?? $message->status }}</div>
                            @if($message->complianceBlockLabel())
                                <div class="mt-1 text-xs font-semibold text-amber-700">{{ $message->complianceBlockLabel() }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $message->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $messages->appends(request()->query())->links() }}</div>
</x-layouts.noia>
