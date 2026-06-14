<x-layouts.noia title="Usuarios" header="Usuarios">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex gap-2">
            <input class="noia-input" type="text" name="search" value="{{ request('search') }}" placeholder="Buscar nombre o email">
            <button class="noia-btn-primary">Buscar</button>
        </form>
        <a href="{{ route('users.create') }}" class="noia-btn-success">Nuevo usuario</a>
    </div>

    <div class="noia-table-wrap">
        <table class="noia-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <span class="noia-badge-neutral">{{ $role->label }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="{{ $user->is_active ? 'noia-badge-success' : 'noia-badge-danger' }}">
                                {{ $user->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-right">
                            <a class="noia-link" href="{{ route('users.edit', $user) }}">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500">No hay usuarios para mostrar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.noia>
