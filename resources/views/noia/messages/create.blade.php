<x-layouts.noia title="Enviar Mensaje" header="Enviar mensaje">
    <div class="grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('messages.send-text') }}" class="noia-card">@csrf <h3 class="mb-4 font-semibold">Texto</h3>@include('noia.messages.partials.fields')<textarea class="noia-textarea mt-3" name="body" placeholder="Mensaje"></textarea><button class="noia-btn-primary mt-4">Enviar texto</button></form>
        <form method="POST" enctype="multipart/form-data" action="{{ route('messages.send-image') }}" class="noia-card">@csrf <h3 class="mb-4 font-semibold">Imagen</h3>@include('noia.messages.partials.fields')<textarea class="noia-textarea mt-3" name="body" placeholder="Texto opcional"></textarea><input class="noia-file-input mt-3" type="file" name="file"><button class="noia-btn-success mt-4">Enviar imagen</button></form>
        <form method="POST" enctype="multipart/form-data" action="{{ route('messages.send-document') }}" class="noia-card">@csrf <h3 class="mb-4 font-semibold">Documento</h3>@include('noia.messages.partials.fields')<textarea class="noia-textarea mt-3" name="body" placeholder="Texto opcional"></textarea><input class="noia-file-input mt-3" type="file" name="file"><button class="noia-btn-info mt-4">Enviar documento</button></form>
    </div>
</x-layouts.noia>
