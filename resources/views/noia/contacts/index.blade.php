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
        <a href="{{ route('contacts.create') }}" class="noia-btn-success">Nuevo contacto</a>
    </div>
    <div class="noia-table-wrap">
        <table class="noia-table">
            <thead class="bg-slate-50 text-left text-slate-500"><tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Teléfono</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr></thead>
            <tbody>
                @foreach($contacts as $contact)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $contact->full_name }}</td>
                        <td class="px-4 py-3">{{ $contact->primary_phone }}</td>
                        <td class="px-4 py-3">{{ $statusLabels[$contact->status] ?? $contact->status }}</td>
                        <td class="px-4 py-3 text-right"><a class="noia-link" href="{{ route('contacts.show', $contact) }}">Ver</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contacts->links() }}</div>
</x-layouts.noia>
