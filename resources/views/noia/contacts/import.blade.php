<x-layouts.noia title="Importar contactos" header="Importar contactos">
    <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
        <form method="POST" enctype="multipart/form-data" action="{{ route('contacts.import.store') }}" class="noia-card space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700" for="file">Archivo CSV o XLSX</label>
                <input id="file" class="noia-file-input" type="file" name="file" accept=".csv,.txt,.xlsx">
                @error('file') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button class="noia-btn-primary">Importar contactos</button>
        </form>

        <div class="noia-card-soft">
            <h3 class="font-semibold text-slate-900">Columnas aceptadas</h3>
            <ul class="mt-3 space-y-2 text-sm text-slate-600">
                <li><strong>first_name</strong> o <strong>nombre</strong></li>
                <li><strong>last_name</strong> o <strong>apellido</strong></li>
                <li><strong>email</strong> o <strong>correo</strong></li>
                <li><strong>primary_phone</strong>, <strong>phone</strong> o <strong>telefono</strong></li>
                <li><strong>status</strong> o <strong>estado</strong>: active, blocked, no_contact, invalid</li>
            </ul>
        </div>
    </div>

    @if(session('import_result'))
        @php($result = session('import_result'))
        <div class="noia-card mt-6">
            <h3 class="font-semibold">Resultado</h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="noia-card-soft">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Creados</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $result['created'] }}</p>
                </div>
                <div class="noia-card-soft">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Omitidos</p>
                    <p class="mt-1 text-2xl font-semibold">{{ $result['skipped'] }}</p>
                </div>
                <div class="noia-card-soft">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Errores</p>
                    <p class="mt-1 text-2xl font-semibold">{{ count($result['errors']) }}</p>
                </div>
            </div>

            @if($result['errors'])
                <div class="mt-5">
                    <a href="{{ route('contacts.import.errors') }}" class="noia-btn-secondary">Descargar errores CSV</a>
                </div>

                <div class="mt-5 overflow-hidden rounded-lg border border-amber-200">
                    <table class="min-w-full text-sm">
                        <thead class="bg-amber-50 text-left text-amber-900">
                            <tr>
                                <th class="px-4 py-3">Fila</th>
                                <th class="px-4 py-3">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($result['errors'] as $error)
                                <tr class="border-t border-amber-100">
                                    <td class="px-4 py-3">{{ $error['row'] }}</td>
                                    <td class="px-4 py-3">{{ $error['message'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</x-layouts.noia>
