<x-layouts.noia title="Conversaciones" header="Conversaciones">
    @php
        $statusLabels = [
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
        ];
    @endphp
    <form method="GET" class="noia-filter-bar mb-4 grid gap-3 md:grid-cols-6">
        <input class="noia-input" name="search" value="{{ request('search') }}" placeholder="Buscar contacto o teléfono">
        <select class="noia-select" name="status">
            <option value="">Estado</option>
            @foreach($statusLabels as $status => $label)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
            @endforeach
        </select>
        <select class="noia-select" name="assigned_user_id">
            <option value="">Asignado</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        <input class="noia-input" type="date" name="date_from" value="{{ request('date_from') }}">
        <input class="noia-input" type="date" name="date_to" value="{{ request('date_to') }}">
        <button class="noia-btn-primary">Filtrar</button>
    </form>
    <div class="space-y-3">
        @foreach($conversations as $conversation)
            <a href="{{ route('conversations.show', $conversation) }}" class="block rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm ring-1 ring-slate-100 transition hover:-translate-y-0.5 hover:border-slate-300">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold">{{ $conversation->contact->full_name }}</p>
                        <p class="text-sm text-slate-500">{{ $conversation->contact->primary_phone }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm">{{ $statusLabels[$conversation->status] ?? $conversation->status }}</p>
                        <p class="text-xs text-slate-400">{{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</p>
                        <p class="text-xs text-slate-400">{{ optional($conversation->last_message_at)->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
    <div class="mt-4">{{ $conversations->appends(request()->query())->links() }}</div>
</x-layouts.noia>
