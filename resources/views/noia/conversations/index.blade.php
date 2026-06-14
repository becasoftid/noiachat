<x-layouts.noia title="Conversaciones" header="Conversaciones">
    @php
        $statusLabels = [
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
        ];
    @endphp
    <form method="GET" class="noia-filter-bar mb-4 grid gap-3 md:grid-cols-7">
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
        <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
            <input class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100" type="checkbox" name="mine" value="1" @checked(request()->boolean('mine'))>
            <span>Mis conversaciones</span>
        </label>
        <button class="noia-btn-primary">Filtrar</button>
    </form>
    <div
        x-data="App.conversationInbox($el.dataset.refreshUrl)"
        x-init="start()"
        x-on:beforeunload.window="stop()"
        data-refresh-url="{{ route('conversations.refresh', request()->query()) }}"
    >
        <div x-ref="list">
            @include('noia.conversations.partials.list', ['conversations' => $conversations, 'statusLabels' => $statusLabels])
        </div>
    </div>
</x-layouts.noia>
