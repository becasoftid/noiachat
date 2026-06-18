<x-layouts.noia title="Tablero" header="Tablero">
    <form method="GET" class="noia-filter-bar mb-6 grid gap-3 md:grid-cols-4">
        @if(($branches ?? collect())->isNotEmpty())
            <select class="noia-select" name="branch_id">
                <option value="">Todas las sedes</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        @endif
        <input class="noia-input" type="date" name="date_from" value="{{ request('date_from') }}">
        <input class="noia-input" type="date" name="date_to" value="{{ request('date_to') }}">
        <button class="noia-btn-primary">Filtrar</button>
    </form>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($stats as $label => $value)
            <div class="noia-card p-5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</x-layouts.noia>
