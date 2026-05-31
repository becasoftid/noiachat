<form method="POST" action="{{ $action }}" class="noia-card space-y-4">
    @php
        $statusLabels = [
            'active' => 'Activo',
            'blocked' => 'Bloqueado',
            'no_contact' => 'No contactar',
            'invalid' => 'Inválido',
        ];
    @endphp
    @csrf
    @if($method !== 'POST') @method($method) @endif
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <input class="noia-input" name="first_name" placeholder="Nombre" value="{{ old('first_name', $contact->first_name ?? '') }}">
            @error('first_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <input class="noia-input" name="last_name" placeholder="Apellido" value="{{ old('last_name', $contact->last_name ?? '') }}">
            @error('last_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <input class="noia-input" name="email" placeholder="Email" value="{{ old('email', $contact->email ?? '') }}">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <input class="noia-input" name="primary_phone" placeholder="Teléfono" value="{{ old('primary_phone', $contact->primary_phone ?? '') }}">
            @error('primary_phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <select class="noia-select" name="status">@foreach($statusLabels as $status => $label)<option value="{{ $status }}" @selected(old('status', $contact->status ?? 'active') === $status)>{{ $label }}</option>@endforeach</select>
            @error('status') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <button class="noia-btn-primary">Guardar</button>
</form>
