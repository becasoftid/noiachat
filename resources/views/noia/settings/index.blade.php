<x-layouts.noia title="Configuración" header="Configuración">
    @php
        $maskSecret = function (?string $value): string {
            if (! filled($value)) {
                return 'No configurado';
            }

            $visible = substr($value, -4);

            return 'Configurado · ****'.$visible;
        };
    @endphp
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
                        <div class="mt-3 grid gap-3">
                            <input class="noia-input w-full text-sm" name="settings[provider]" value="{{ data_get($channel->settings, 'provider') }}" placeholder="Proveedor">
                            <input class="noia-input w-full text-sm" name="settings[api_base_url]" value="{{ data_get($channel->settings, 'api_base_url') }}" placeholder="Graph API base URL">
                            <input class="noia-input w-full text-sm" name="settings[phone_number_id]" value="{{ data_get($channel->settings, 'phone_number_id') }}" placeholder="Phone Number ID">
                            <input class="noia-input w-full text-sm" name="settings[business_account_id]" value="{{ data_get($channel->settings, 'business_account_id') }}" placeholder="WhatsApp Business Account ID">
                            <label class="grid gap-1">
                                <span class="text-xs font-semibold text-slate-500">Access token: {{ $maskSecret(data_get($channel->settings, 'access_token')) }}</span>
                                <input class="noia-input w-full text-sm" type="password" autocomplete="new-password" name="settings[access_token]" placeholder="Nuevo access token">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-xs font-semibold text-slate-500">Webhook token: {{ $maskSecret(data_get($channel->settings, 'webhook_verify_token')) }}</span>
                                <input class="noia-input w-full text-sm" type="password" autocomplete="new-password" name="settings[webhook_verify_token]" placeholder="Nuevo webhook verify token">
                            </label>
                            <label class="grid gap-1">
                                <span class="text-xs font-semibold text-slate-500">App secret: {{ $maskSecret(data_get($channel->settings, 'app_secret')) }}</span>
                                <input class="noia-input w-full text-sm" type="password" autocomplete="new-password" name="settings[app_secret]" placeholder="Nuevo app secret">
                            </label>
                            <div class="grid gap-3 border-t border-slate-200 pt-3 md:grid-cols-2">
                                <label class="grid gap-1">
                                    <span class="text-xs font-semibold text-slate-500">Expira</span>
                                    <input class="noia-input w-full text-sm" type="date" name="settings[access_token_expires_at]" value="{{ data_get($channel->settings, 'access_token_expires_at') }}">
                                </label>
                                <label class="grid gap-1">
                                    <span class="text-xs font-semibold text-slate-500">Última rotación</span>
                                    <input class="noia-input w-full text-sm" type="date" name="settings[access_token_rotated_at]" value="{{ data_get($channel->settings, 'access_token_rotated_at') }}">
                                </label>
                                <label class="grid gap-1 md:col-span-2">
                                    <span class="text-xs font-semibold text-slate-500">Responsable</span>
                                    <input class="noia-input w-full text-sm" name="settings[access_token_responsible]" value="{{ data_get($channel->settings, 'access_token_responsible') }}" placeholder="Nombre o equipo responsable">
                                </label>
                                <label class="grid gap-1 md:col-span-2">
                                    <span class="text-xs font-semibold text-slate-500">Procedimiento de rotación</span>
                                    <textarea class="noia-textarea text-sm" name="settings[access_token_rotation_procedure]" rows="3" placeholder="Pasos internos para rotar token, validar envío y reiniciar workers">{{ data_get($channel->settings, 'access_token_rotation_procedure') }}</textarea>
                                </label>
                            </div>
                        </div>
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

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-semibold">Plantillas</h3>
                    <p class="mt-1 text-sm text-slate-500">Sincroniza nombre, idioma, estado, categoria y variables desde Meta.</p>
                </div>
                <form method="POST" action="{{ route('settings.templates.sync-whatsapp') }}">
                    @csrf
                    <button class="noia-btn-primary text-sm">Sincronizar Meta</button>
                </form>
            </div>
            <div class="mt-4 space-y-4">
                @foreach($templates as $template)
                    <div class="noia-card-soft">
                        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                            @if($template->meta_status)
                                <span class="rounded-full px-2 py-1 font-semibold {{ $template->meta_status === 'APPROVED' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    Meta: {{ $template->meta_status }}
                                </span>
                            @endif
                            @if($template->meta_category)
                                <span class="rounded-full bg-slate-100 px-2 py-1 font-semibold text-slate-600">
                                    {{ $template->meta_category }}
                                </span>
                            @endif
                            @if($template->currentVersion)
                                <span class="rounded-full bg-cyan-100 px-2 py-1 font-semibold text-cyan-700">
                                    {{ $template->currentVersion->expectedVariableCount() }} variables
                                </span>
                            @endif
                            @if($template->synced_at)
                                <span class="text-slate-500">Sync: {{ $template->synced_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </div>
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
                                <div class="text-sm text-slate-500">Versión actual: {{ $template->currentVersion?->version ?? '-' }} · Idioma Meta: {{ $template->currentVersion?->language ?? '-' }}</div>
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
