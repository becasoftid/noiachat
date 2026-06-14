@php
    $selectedRoles = collect(old('roles', $managedUser?->roles->pluck('id')->all() ?? []))->map(fn ($role) => (int) $role)->all();
@endphp

<form method="POST" action="{{ $action }}" class="noia-card space-y-5">
    @csrf
    @if($method !== 'POST') @method($method) @endif

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="name">Nombre</label>
            <input id="name" class="noia-input" name="name" value="{{ old('name', $managedUser->name ?? '') }}" autocomplete="name">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="email">Email</label>
            <input id="email" class="noia-input" type="email" name="email" value="{{ old('email', $managedUser->email ?? '') }}" autocomplete="username">
            @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="password">Contrasena</label>
            <input id="password" class="noia-input" type="password" name="password" autocomplete="new-password">
            @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold text-slate-700" for="password_confirmation">Confirmar contrasena</label>
            <input id="password_confirmation" class="noia-input" type="password" name="password_confirmation" autocomplete="new-password">
        </div>
    </div>

    <div class="noia-card-soft">
        <p class="text-sm font-semibold text-slate-800">Roles</p>
        <div class="mt-3 grid gap-3 sm:grid-cols-3">
            @foreach($roles as $role)
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 bg-white px-3 py-3 text-sm font-semibold text-slate-700">
                    <input class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $selectedRoles, true))>
                    <span>{{ $role->label }}</span>
                </label>
            @endforeach
        </div>
        @error('roles') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('roles.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="is_active" value="0">
        <input class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100" type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser->is_active ?? true))>
        <span>Usuario activo</span>
    </label>
    @error('is_active') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror

    <div class="flex flex-wrap gap-3">
        <button class="noia-btn-primary">Guardar</button>
        <a href="{{ route('users.index') }}" class="noia-btn-secondary">Cancelar</a>
    </div>
</form>
