<x-layouts.noia title="Crear Contacto" header="Crear contacto">@include('noia.contacts.partials.form', ['action' => route('contacts.store'), 'method' => 'POST', 'contact' => null])</x-layouts.noia>
