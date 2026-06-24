<x-layouts.noia title="WhatsApp" header="Canales WhatsApp">
    @php
        $maskSecret = function (?string $value): string {
            if (! filled($value)) {
                return 'No configurado';
            }

            return 'Configurado · ****'.substr($value, -4);
        };

        $statusBadge = fn (string $level): string => match ($level) {
            'ready' => 'noia-badge-success',
            'attention' => 'border-amber-200 bg-amber-50 text-amber-700',
            default => 'noia-badge-neutral',
        };

        $activeChannels = $channels->where('is_active', true)->count();
        $attentionChannels = $channels->filter(fn ($channel) => ($channel->getAttribute('operational_status')['level'] ?? 'attention') !== 'ready')->count();
        $totalMessages = $channels->sum('messages_count');
        $totalConversations = $channels->sum('conversations_count');
    @endphp

    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-6">
            <section class="noia-card">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Integracion Meta</p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-950">WhatsApp Cloud API</h3>
                        <p class="mt-1 text-sm text-slate-500">Gestiona credenciales, pruebas y plantillas de los canales de la empresa activa.</p>
                    </div>
                    <span class="noia-badge noia-badge-neutral">Empresa activa</span>
                </div>

                <dl class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Activos</dt>
                        <dd class="mt-1 text-2xl font-bold text-emerald-900">{{ $activeChannels }}</dd>
                    </div>
                    <div class="rounded-lg border border-amber-100 bg-amber-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-amber-700">Revision</dt>
                        <dd class="mt-1 text-2xl font-bold text-amber-900">{{ $attentionChannels }}</dd>
                    </div>
                    <div class="rounded-lg border border-cyan-100 bg-cyan-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-cyan-700">Mensajes</dt>
                        <dd class="mt-1 text-2xl font-bold text-cyan-900">{{ $totalMessages }}</dd>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Conversaciones</dt>
                        <dd class="mt-1 text-2xl font-bold text-slate-950">{{ $totalConversations }}</dd>
                    </div>
                </dl>
            </section>

            <details class="noia-card group" @if($channels->isEmpty()) open @endif>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 [&::-webkit-details-marker]:hidden">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Nuevo canal</p>
                        <h3 class="mt-1 text-lg font-semibold text-slate-950">Crear configuracion WhatsApp</h3>
                    </div>
                    <span class="noia-btn-success h-10 px-4">Crear canal</span>
                </summary>

                <form method="POST" action="{{ route('whatsapp.channels.store') }}" class="mt-6 grid gap-4 border-t border-slate-200 pt-6">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nombre</span>
                            <input class="noia-input" name="name" value="{{ old('name', 'WhatsApp') }}" required>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sede</span>
                            <select class="noia-select" name="branch_id">
                                <option value="">Toda la empresa</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Phone Number ID</span>
                            <input class="noia-input" name="settings[phone_number_id]" value="{{ old('settings.phone_number_id') }}" inputmode="numeric">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">WABA ID</span>
                            <input class="noia-input" name="settings[business_account_id]" value="{{ old('settings.business_account_id') }}" inputmode="numeric">
                        </label>
                        <label class="grid gap-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Access token</span>
                            <input class="noia-input" type="password" autocomplete="new-password" name="settings[access_token]">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Webhook verify token</span>
                            <input class="noia-input" type="password" autocomplete="new-password" name="settings[webhook_verify_token]">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">App secret</span>
                            <input class="noia-input" type="password" autocomplete="new-password" name="settings[app_secret]">
                        </label>
                        <label class="grid gap-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Graph API base URL</span>
                            <input class="noia-input" name="settings[api_base_url]" value="{{ old('settings.api_base_url', 'https://graph.facebook.com/v21.0') }}">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Expira</span>
                            <input class="noia-input" type="date" name="settings[access_token_expires_at]" value="{{ old('settings.access_token_expires_at') }}">
                        </label>
                        <label class="grid gap-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Ultima rotacion</span>
                            <input class="noia-input" type="date" name="settings[access_token_rotated_at]" value="{{ old('settings.access_token_rotated_at') }}">
                        </label>
                        <label class="grid gap-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Responsable</span>
                            <input class="noia-input" name="settings[access_token_responsible]" value="{{ old('settings.access_token_responsible') }}">
                        </label>
                        <label class="grid gap-2 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Procedimiento de rotacion</span>
                            <textarea class="noia-textarea" name="settings[access_token_rotation_procedure]" rows="3">{{ old('settings.access_token_rotation_procedure') }}</textarea>
                        </label>
                        <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                            Activo
                        </label>
                        <button class="noia-btn-success">Crear canal</button>
                    </div>
                </form>
            </details>

            <section class="space-y-4">
                @forelse($channels as $channel)
                    @php($state = $channel->getAttribute('operational_status'))
                    <article class="noia-card">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-xl font-semibold text-slate-950">{{ $channel->name }}</h3>
                                    <span class="noia-badge {{ $channel->is_active ? 'noia-badge-success' : 'noia-badge-neutral' }}">
                                        {{ $channel->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <span class="noia-badge {{ $statusBadge($state['level']) }}">
                                        {{ $state['label'] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-500">{{ $channel->branch?->name ?? 'Toda la empresa' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('whatsapp.channels.test', $channel) }}">
                                    @csrf
                                    <button class="noia-btn-secondary h-10 px-4">Probar conexion</button>
                                </form>
                                <form method="POST" action="{{ route('whatsapp.channels.sync-templates', $channel) }}">
                                    @csrf
                                    <button class="noia-btn-secondary h-10 px-4">Sincronizar plantillas</button>
                                </form>
                            </div>
                        </div>

                        @if($state['missing'] !== [] || $state['warnings'] !== [])
                            <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                <p class="font-semibold">Pendientes del canal</p>
                                <ul class="mt-2 list-inside list-disc space-y-1">
                                    @foreach($state['missing'] as $missing)
                                        <li>Configurar {{ $missing }}</li>
                                    @endforeach
                                    @foreach($state['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Phone Number ID</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ data_get($channel->settings, 'phone_number_id') ?: 'No configurado' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">WABA ID</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ data_get($channel->settings, 'business_account_id') ?: 'No configurado' }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Token</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ $maskSecret(data_get($channel->settings, 'access_token')) }}</p>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Expira</p>
                                <p class="mt-1 truncate text-sm font-semibold text-slate-800">{{ data_get($channel->settings, 'access_token_expires_at') ?: 'Sin fecha' }}</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 border-t border-slate-200 pt-5 sm:grid-cols-3">
                            <div>
                                <p class="text-2xl font-semibold text-slate-950">{{ $channel->messages_count }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Mensajes</p>
                            </div>
                            <div>
                                <p class="text-2xl font-semibold text-slate-950">{{ $channel->conversations_count }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Conversaciones</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-950">
                                    {{ data_get($channel->settings, 'last_connection_test.tested_at') ?: 'Sin prueba' }}
                                </p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Ultima prueba</p>
                            </div>
                        </div>

                        @if(data_get($channel->settings, 'last_connection_test.tested_at'))
                            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
                                <p class="font-semibold">Conexion validada</p>
                                <p class="mt-1">
                                    {{ data_get($channel->settings, 'last_connection_test.verified_name') ?: data_get($channel->settings, 'last_connection_test.display_phone_number', 'Meta valido el canal') }}
                                </p>
                            </div>
                        @endif

                        <details class="mt-5 rounded-lg border border-slate-200 bg-slate-50/70 p-4 group">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-sm font-semibold text-slate-800 [&::-webkit-details-marker]:hidden">
                                <span>Editar configuracion</span>
                                <span class="text-xs text-slate-500 group-open:hidden">Mostrar campos</span>
                                <span class="hidden text-xs text-slate-500 group-open:inline">Ocultar campos</span>
                            </summary>

                            <form method="POST" action="{{ route('whatsapp.channels.update', $channel) }}" class="mt-5 grid gap-4 border-t border-slate-200 pt-5">
                                @csrf
                                @method('PATCH')
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nombre</span>
                                        <input class="noia-input" name="name" value="{{ old('name', $channel->name) }}" required>
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sede</span>
                                        <select class="noia-select" name="branch_id">
                                            <option value="" @selected($channel->branch_id === null)>Toda la empresa</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected($channel->branch_id === $branch->id)>{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Phone Number ID</span>
                                        <input class="noia-input" name="settings[phone_number_id]" value="{{ old('settings.phone_number_id', data_get($channel->settings, 'phone_number_id')) }}" inputmode="numeric">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">WABA ID</span>
                                        <input class="noia-input" name="settings[business_account_id]" value="{{ old('settings.business_account_id', data_get($channel->settings, 'business_account_id')) }}" inputmode="numeric">
                                    </label>
                                    <label class="grid gap-2 md:col-span-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Access token: {{ $maskSecret(data_get($channel->settings, 'access_token')) }}</span>
                                        <input class="noia-input" type="password" autocomplete="new-password" name="settings[access_token]">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Webhook token: {{ $maskSecret(data_get($channel->settings, 'webhook_verify_token')) }}</span>
                                        <input class="noia-input" type="password" autocomplete="new-password" name="settings[webhook_verify_token]">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">App secret: {{ $maskSecret(data_get($channel->settings, 'app_secret')) }}</span>
                                        <input class="noia-input" type="password" autocomplete="new-password" name="settings[app_secret]">
                                    </label>
                                    <label class="grid gap-2 md:col-span-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Graph API base URL</span>
                                        <input class="noia-input" name="settings[api_base_url]" value="{{ old('settings.api_base_url', data_get($channel->settings, 'api_base_url', 'https://graph.facebook.com/v21.0')) }}">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Expira</span>
                                        <input class="noia-input" type="date" name="settings[access_token_expires_at]" value="{{ old('settings.access_token_expires_at', data_get($channel->settings, 'access_token_expires_at')) }}">
                                    </label>
                                    <label class="grid gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Ultima rotacion</span>
                                        <input class="noia-input" type="date" name="settings[access_token_rotated_at]" value="{{ old('settings.access_token_rotated_at', data_get($channel->settings, 'access_token_rotated_at')) }}">
                                    </label>
                                    <label class="grid gap-2 md:col-span-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Responsable</span>
                                        <input class="noia-input" name="settings[access_token_responsible]" value="{{ old('settings.access_token_responsible', data_get($channel->settings, 'access_token_responsible')) }}">
                                    </label>
                                    <label class="grid gap-2 md:col-span-2">
                                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Procedimiento de rotacion</span>
                                        <textarea class="noia-textarea" name="settings[access_token_rotation_procedure]" rows="3">{{ old('settings.access_token_rotation_procedure', data_get($channel->settings, 'access_token_rotation_procedure')) }}</textarea>
                                    </label>
                                    <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($channel->is_active) class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100">
                                        Activo
                                    </label>
                                    <button class="noia-btn-primary">Guardar canal</button>
                                </div>
                            </form>
                        </details>
                    </article>
                @empty
                    <div class="noia-card">
                        <h3 class="text-lg font-semibold text-slate-950">Sin canales WhatsApp</h3>
                        <p class="mt-2 text-sm text-slate-500">La empresa activa aun no tiene un canal WhatsApp comercial.</p>
                    </div>
                @endforelse
            </section>
        </div>

        <aside class="space-y-6 xl:sticky xl:top-6">
            <section class="noia-card">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">Checklist Meta</p>
                <h3 class="mt-2 text-lg font-semibold text-slate-950">Datos necesarios</h3>
                <div class="mt-5 space-y-3 text-sm text-slate-600">
                    <details class="rounded-lg border border-slate-200 bg-slate-50 p-3" open>
                        <summary class="cursor-pointer list-none font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">1. Numero y cuenta</summary>
                        <p class="mt-2">Copia `Phone Number ID` y `WhatsApp Business Account ID` desde Meta Developers.</p>
                    </details>
                    <details class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <summary class="cursor-pointer list-none font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">2. Seguridad</summary>
                        <p class="mt-2">Guarda access token, webhook verify token, app secret, responsable y fechas de rotacion.</p>
                    </details>
                    <details class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <summary class="cursor-pointer list-none font-semibold text-slate-900 [&::-webkit-details-marker]:hidden">3. Validacion</summary>
                        <p class="mt-2">Prueba conexion y sincroniza plantillas aprobadas despues de guardar credenciales.</p>
                    </details>
                </div>
            </section>

            <section class="noia-card">
                <h3 class="font-semibold text-slate-950">Flujo recomendado</h3>
                <ol class="mt-4 space-y-3 text-sm text-slate-600">
                    <li class="rounded-lg bg-slate-50 p-3"><strong class="text-slate-900">1.</strong> Crear o editar el canal.</li>
                    <li class="rounded-lg bg-slate-50 p-3"><strong class="text-slate-900">2.</strong> Probar conexion con Meta.</li>
                    <li class="rounded-lg bg-slate-50 p-3"><strong class="text-slate-900">3.</strong> Sincronizar plantillas y operar conversaciones.</li>
                </ol>
            </section>
        </aside>
    </div>
</x-layouts.noia>
