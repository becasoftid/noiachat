@php
    $displayTimezone = config('app.display_timezone', 'America/Bogota');
    $contactName = $conversation->contact->full_name;
    $backToInboxUrl = route('conversations.index', request()->except(['conversation']));
    $lastActivity = $conversation->last_message_at?->copy()->timezone($displayTimezone);
    $windowUntil = $customerCareWindowUntil?->copy()->timezone($displayTimezone);
@endphp

<section class="flex min-h-0 flex-col bg-[#f7fbfc]">
    <header class="border-b border-slate-200 bg-white px-4 py-3">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <a
                    href="{{ $backToInboxUrl }}"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-cyan-100 lg:hidden"
                    aria-label="Volver al inbox"
                    title="Volver al inbox"
                >
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>

                <span
                    x-show="! detailsOpen"
                    x-transition.opacity
                    class="inline-flex h-10 min-w-0 items-center gap-2 rounded-lg bg-emerald-50 px-3 text-sm font-semibold text-emerald-800 lg:hidden xl:inline-flex"
                >
                    <span class="max-w-[220px] truncate">{{ $contactName }}</span>
                    <span class="hidden h-1 w-1 rounded-full bg-emerald-500 sm:inline-block"></span>
                    <span class="hidden text-emerald-700 sm:inline">{{ $conversation->channel?->name ?? 'WhatsApp' }}</span>
                </span>

                <span class="inline-flex h-10 min-w-0 items-center gap-2 rounded-lg bg-slate-50 px-3 text-sm font-semibold text-slate-600">
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" stroke-linecap="round" />
                        <circle cx="9" cy="7" r="4" />
                    </svg>
                    <span class="truncate">{{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</span>
                </span>

                @can('messages.send')
                    @if($conversation->assigned_user_id !== auth()->id())
                        <form method="POST" action="{{ route('conversations.assign-me', $conversation) }}">
                            @csrf
                            @method('PUT')
                            <button class="noia-btn-success h-10 px-4">Asignarme</button>
                        </form>
                    @endif
                @endcan
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('conversations.assign', $conversation) }}" class="flex gap-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="assigned_user_id" value="{{ $conversation->assigned_user_id }}">
                    <label class="sr-only" for="conversation-status">Estado</label>
                    <select id="conversation-status" class="noia-select h-10 min-w-[145px] bg-white" name="status" onchange="this.form.submit()">
                        @foreach($statusLabels as $status => $label)
                            <option value="{{ $status }}" @selected($conversation->status === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                    x-bind:aria-label="detailsOpen ? 'Ocultar detalles' : 'Ver detalles'"
                    x-bind:title="detailsOpen ? 'Ocultar detalles' : 'Ver detalles'"
                    x-on:click="detailsOpen = ! detailsOpen"
                >
                    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 13v5M12 6h.01" stroke-linecap="round" />
                        <circle cx="12" cy="12" r="9" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="@class(['mt-3 rounded-lg border px-3 py-2 text-sm', 'border-amber-200 bg-amber-50 text-amber-900' => $customerCareWindowClosed, 'border-emerald-200 bg-emerald-50 text-emerald-800' => ! $customerCareWindowClosed])">
            @if($customerCareWindowClosed)
                <p class="font-semibold">Ventana 24h cerrada. Usa una plantilla aprobada para responder.</p>
            @else
                <p class="font-semibold">Ventana de atención abierta{{ $windowUntil ? ' hasta las '.$windowUntil->format('H:i') : '' }}. Puedes enviar mensajes libres.</p>
            @endif
        </div>
    </header>

    <div class="min-h-0 flex-1 overflow-y-auto bg-[#f7fbfc] px-4 py-5 2xl:px-6">
        <div class="mx-auto w-full max-w-5xl space-y-3">
            @php $lastDate = null; @endphp
            @forelse($timeline as $item)
                @php
                    $createdAt = $item->created_at?->copy()->timezone($displayTimezone);
                    $readAt = $item->read_at?->copy()->timezone($displayTimezone);
                    $deliveredAt = $item->delivered_at?->copy()->timezone($displayTimezone);
                    $sentAt = $item->sent_at?->copy()->timezone($displayTimezone);
                    $dateKey = optional($createdAt)->format('Y-m-d');
                    $providerError = $item->provider_logs->first(fn ($log) => $log->hasError());
                    $isBlocked = $item->status === 'blocked_by_policy';
                    $statusHint = match ($item->status) {
                        'read' => 'Leído'.($readAt ? ' '.$readAt->format('H:i') : ''),
                        'delivered' => 'Entregado'.($deliveredAt ? ' '.$deliveredAt->format('H:i') : ''),
                        'sent' => 'Enviado'.($sentAt ? ' '.$sentAt->format('H:i') : ''),
                        'queued' => 'En cola',
                        'sending' => 'Enviando',
                        'failed' => 'Fallido',
                        'blocked_by_policy' => 'Bloqueado',
                        default => $item->status ? ($messageStatusLabels[$item->status] ?? $item->status) : null,
                    };
                @endphp

                @if($dateKey && $dateKey !== $lastDate)
                    <div class="flex justify-center py-2">
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm">
                            {{ optional($createdAt)->translatedFormat('d \\d\\e F \\d\\e Y') }}
                        </span>
                    </div>
                    @php $lastDate = $dateKey; @endphp
                @endif

                @if($isBlocked)
                    <div class="mx-auto max-w-3xl rounded-lg border border-amber-100 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Sistema · {{ optional($createdAt)->format('H:i') }}</p>
                        <p class="mt-2 font-semibold text-slate-900">Envío bloqueado: {{ $item->compliance_block_label }}</p>
                        <p class="mt-1">{{ $item->compliance_block_description }}</p>
                    </div>
                @else
                    <div class="@if($item->direction === 'outbound') ml-auto rounded-br-sm bg-[#d9fdd3] @else rounded-bl-sm bg-white @endif w-fit max-w-[92%] rounded-lg px-4 py-3 text-slate-950 shadow-sm ring-1 ring-black/5 lg:max-w-[680px]">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $item->direction === 'outbound' ? 'Saliente' : 'Entrante' }}</p>
                            <p class="shrink-0 text-[11px] text-slate-500">{{ optional($createdAt)->format('H:i') }}</p>
                        </div>

                        @if(filled($item->body))
                            <p class="mt-2 whitespace-pre-wrap text-sm leading-relaxed">{{ $item->body }}</p>
                        @endif

                        @if($item->attachments->isNotEmpty())
                            <div class="mt-3 space-y-2 rounded-lg bg-white/70 p-3 text-sm">
                                @foreach($item->attachments as $attachment)
                                    <a href="{{ asset('storage/'.$attachment->mediaFile->path) }}" target="_blank" class="block noia-link">
                                        {{ $attachment->mediaFile->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($item->status)
                            <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                                <span>{{ $messageStatusLabels[$item->status] ?? $item->status }}</span>
                                @if($item->direction === 'outbound')
                                    <span
                                        title="{{ $statusHint }}"
                                        class="@class([
                                            'inline-flex min-w-[22px] justify-end text-sm font-bold leading-none',
                                            'text-sky-600' => $item->status === 'read',
                                            'text-slate-400' => $item->status !== 'read',
                                        ])"
                                        aria-label="{{ $statusHint }}"
                                    >
                                        @if($item->status === 'read' || $item->status === 'delivered')
                                            &check;&check;
                                        @elseif($item->status === 'sent')
                                            &check;
                                        @elseif(in_array($item->status, ['queued', 'sending'], true))
                                            &middot;&middot;&middot;
                                        @elseif(in_array($item->status, ['failed', 'bounced'], true))
                                            !
                                        @endif
                                    </span>
                                @endif
                            </p>
                        @endif

                        @if($providerError)
                            <details class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                                <summary class="cursor-pointer font-semibold">Error de Meta</summary>
                                @if($providerError->errorCode())
                                    <p class="mt-1">Código: {{ $providerError->errorCode() }}</p>
                                @endif
                                @if($providerError->errorMessage())
                                    <p class="mt-1">{{ $providerError->errorMessage() }}</p>
                                @endif
                                @if($providerError->errorDetails())
                                    <p class="mt-1">{{ $providerError->errorDetails() }}</p>
                                @endif
                            </details>
                        @endif
                    </div>
                @endif
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                    Esta conversación aún no tiene mensajes.
                </div>
            @endforelse
        </div>
    </div>

    <footer class="border-t border-slate-200 bg-white px-3 py-3 shadow-[0_-16px_40px_rgba(15,23,42,0.06)]">
        <div class="w-full">
            @if($customerCareWindowClosed)
                <textarea class="sr-only" disabled aria-hidden="true"></textarea>
                <form
                    method="POST"
                    action="{{ route('conversations.reply-template', $conversation) }}"
                    class="rounded-lg border border-slate-200 bg-white p-4"
                    x-data="App.templateComposer(@js($templateOptions))"
                >
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-bold text-slate-950">Enviar con plantilla</h3>
                            <p class="mt-1 text-xs text-slate-500">Selecciona una plantilla y completa sus variables.</p>
                        </div>
                        <span class="text-xs font-semibold text-amber-700">{{ $templates->count() }} plantillas disponibles</span>
                    </div>

                    <div class="mt-4 grid gap-3 lg:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.12em] text-slate-500" for="message-template">Plantilla</label>
                            <select id="message-template" class="noia-select bg-white" name="message_template_id" x-model="selectedId" required>
                                <option value="">Selecciona plantilla</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected((int) old('message_template_id') === $template->id)>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Variables de la plantilla</p>
                            <div class="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <template x-if="! selectedTemplate">
                                    <p class="text-sm text-slate-500">Selecciona una plantilla.</p>
                                </template>
                                <template x-for="variable in selectedTemplate?.variables || []" :key="variable.key">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-slate-600" x-text="variable.label"></label>
                                        <input class="noia-input bg-white" type="text" x-model="values[variable.key]" :placeholder="'Ej: valor para ' + variable.label">
                                        <p class="mt-1 text-xs text-slate-400">Campo requerido para esta plantilla.</p>
                                    </div>
                                </template>
                                <template x-if="selectedTemplate && selectedTemplate.variables.length === 0">
                                    <p class="text-sm text-slate-500">Esta plantilla no requiere variables.</p>
                                </template>
                            </div>
                            <input type="hidden" name="variables" x-bind:value="serializedVariables">
                            @error('variables')
                                <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Vista previa del mensaje</p>
                            <div class="min-h-[120px] rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-700" x-text="preview || 'La vista previa aparece al seleccionar una plantilla.'"></div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
                        <div class="flex min-h-[56px] flex-1 items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">
                            <svg aria-hidden="true" class="h-5 w-5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m21.4 11.6-8.9 8.9a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 1 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span>Adjuntos en plantillas quedan sujetos a la configuracion aprobada en Meta.</span>
                        </div>
                        <button class="noia-btn-success min-w-[190px]" x-bind:disabled="! canSubmit" x-bind:class="! canSubmit ? 'opacity-60 cursor-not-allowed hover:translate-y-0' : ''">
                            Enviar plantilla
                        </button>
                    </div>
                </form>
            @else
                <div x-data="{ mode: 'text' }" class="rounded-lg border border-slate-200 bg-white p-2">
                    <div class="mb-2 flex flex-wrap gap-2">
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold" x-bind:class="mode === 'text' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-600'" x-on:click="mode = 'text'">Mensaje</button>
                        <button type="button" class="rounded-lg px-3 py-1.5 text-sm font-semibold" x-bind:class="mode === 'media' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-50 text-slate-600'" x-on:click="mode = 'media'">Adjuntar archivo</button>
                    </div>

                    <form x-show="mode === 'text'" method="POST" action="{{ route('conversations.reply', $conversation) }}" class="grid gap-2 lg:grid-cols-[1fr_auto]">
                        @csrf
                        <h3 class="sr-only">Respuesta de texto</h3>
                        <label class="sr-only" for="conversation-body">Responder conversación</label>
                        <textarea id="conversation-body" class="noia-textarea min-h-[52px] resize-none bg-white py-3" name="body" rows="2" placeholder="Responder conversación"></textarea>
                        <button class="noia-btn-success min-h-[52px] px-6">Enviar</button>
                    </form>

                    <form x-cloak x-show="mode === 'media'" method="POST" enctype="multipart/form-data" action="{{ route('conversations.reply-media', $conversation) }}" class="grid gap-2 lg:grid-cols-[150px_1fr_auto]">
                        @csrf
                        <select class="noia-select bg-white" name="type" aria-label="Tipo de adjunto">
                            <option value="image">Imagen</option>
                            <option value="document">Documento</option>
                        </select>
                        <input class="noia-file-input bg-white" type="file" name="file">
                        <button class="noia-btn-info px-6">Enviar adjunto</button>
                        <textarea class="noia-textarea min-h-[52px] bg-white py-3 lg:col-span-3" name="body" rows="2" placeholder="Texto opcional"></textarea>
                    </form>
                </div>
            @endif
        </div>
    </footer>
</section>

<aside x-cloak x-show="detailsOpen" x-transition.opacity class="hidden min-h-0 overflow-y-auto border-l border-slate-200 bg-white xl:block">
    <div class="p-4">
        @include('noia.conversations.partials.details')
    </div>
</aside>

<div x-cloak x-show="detailsOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/40 xl:hidden" x-on:click="detailsOpen = false"></div>
<aside x-cloak x-show="detailsOpen" x-transition class="fixed inset-y-0 right-0 z-50 w-full max-w-sm overflow-y-auto bg-white shadow-2xl xl:hidden">
    <div class="flex items-center justify-between border-b border-slate-200 p-4">
        <h3 class="font-semibold text-slate-950">Detalles</h3>
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-500" x-on:click="detailsOpen = false" aria-label="Cerrar detalles">×</button>
    </div>
    <div class="p-4">
        @include('noia.conversations.partials.details')
    </div>
</aside>
