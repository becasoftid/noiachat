<x-layouts.noia title="Configuración" header="Configuración">
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="noia-card xl:col-span-1">
            <h3 class="font-semibold">Canales</h3>
            <div class="mt-4 space-y-4">
                @foreach($channels as $channel)
                    <form method="POST" action="{{ route('settings.channels.update', $channel) }}" class="noia-card-soft">
                        @csrf
                        @method('PUT')
                        <div class="flex items-center justify-between">
                            <input class="noia-input w-full" name="name" value="{{ $channel->name }}">
                            <label class="ml-3 flex items-center gap-2 text-xs">
                                <input type="checkbox" name="is_active" value="1" @checked($channel->is_active)>
                                Activo
                            </label>
                        </div>
                        <input class="noia-input mt-3 w-full text-sm" name="settings[provider]" value="{{ data_get($channel->settings, 'provider') }}" placeholder="Proveedor">
                        <p class="mt-2 text-sm text-slate-500">Mensajes: {{ $channel->messages_count }} · Conversaciones: {{ $channel->conversations_count }}</p>
                        <button class="noia-btn-primary mt-3 text-sm">Guardar canal</button>
                    </form>
                @endforeach
            </div>
        </div>
        <div class="noia-card xl:col-span-2">
            <h3 class="font-semibold">Nueva plantilla</h3>
            <form method="POST" action="{{ route('settings.templates.store') }}" class="mt-4 grid gap-3 md:grid-cols-2">
                @csrf
                <select class="noia-select" name="channel_id">
                    @foreach($channels as $channel)
                        <option value="{{ $channel->id }}">{{ $channel->name }}</option>
                    @endforeach
                </select>
                <input class="noia-input" name="name" placeholder="Nombre interno">
                <input class="noia-input" name="external_template_id" placeholder="Template ID externo">
                <input class="noia-input" name="language" value="es" placeholder="Idioma">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" checked>
                    Activa
                </label>
                <textarea class="noia-textarea md:col-span-2" name="body" rows="4" placeholder="Contenido de la plantilla"></textarea>
                <button class="noia-btn-success md:col-span-2">Crear plantilla</button>
            </form>

            <h3 class="mt-8 font-semibold">Plantillas</h3>
            <div class="mt-4 space-y-4">
                @foreach($templates as $template)
                    <div class="noia-card-soft">
                        <form id="template-update-{{ $template->id }}" method="POST" action="{{ route('settings.templates.update', $template) }}">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-3 md:grid-cols-2">
                                <select class="noia-select" name="channel_id">
                                    @foreach($channels as $channel)
                                        <option value="{{ $channel->id }}" @selected($template->channel_id === $channel->id)>{{ $channel->name }}</option>
                                    @endforeach
                                </select>
                                <input class="noia-input" name="name" value="{{ $template->name }}">
                                <input class="noia-input" name="external_template_id" value="{{ $template->external_template_id }}">
                                <input class="noia-input" name="language" value="{{ $template->currentVersion?->language ?? 'es' }}">
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="is_active" value="1" @checked($template->is_active)>
                                    Activa
                                </label>
                                <div class="text-sm text-slate-500">Versión actual: {{ $template->currentVersion?->version ?? '-' }}</div>
                                <textarea class="noia-textarea md:col-span-2" name="body" rows="4">{{ $template->currentVersion?->body }}</textarea>
                            </div>
                        </form>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button form="template-update-{{ $template->id }}" class="noia-btn-primary text-sm">Guardar nueva versión</button>
                            <form method="POST" action="{{ route('settings.templates.toggle', $template) }}">
                                @csrf
                                @method('PATCH')
                                <button class="noia-btn-warning text-sm">{{ $template->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            <form method="POST" action="{{ route('settings.templates.destroy', $template) }}">
                                @csrf
                                @method('DELETE')
                                <button class="noia-btn-danger text-sm">Archivar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.noia>
