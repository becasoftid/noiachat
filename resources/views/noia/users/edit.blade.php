<x-layouts.noia title="Editar usuario" header="Editar usuario">
    @include('noia.users.partials.form', [
        'action' => route('users.update', $managedUser),
        'method' => 'PUT',
        'managedUser' => $managedUser,
        'roles' => $roles,
    ])
</x-layouts.noia>
