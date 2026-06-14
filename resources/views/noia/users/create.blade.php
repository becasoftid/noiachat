<x-layouts.noia title="Nuevo usuario" header="Nuevo usuario">
    @include('noia.users.partials.form', [
        'action' => route('users.store'),
        'method' => 'POST',
        'managedUser' => null,
        'roles' => $roles,
    ])
</x-layouts.noia>
