<section class="flex min-h-[720px] flex-col bg-[#e8f1f3] lg:min-h-0">
    <div class="border-b border-slate-200 bg-white px-5 py-4">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                @php
                    $contactName = $conversation->contact->full_name;
                    $contactInitials = collect(explode(' ', trim($contactName)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->join('');
                @endphp
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white shadow-sm">
                    {{ $contactInitials ?: 'N' }}
                </div>
                <div class="min-w-0">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <h2 class="truncate text-lg font-semibold text-slate-950">{{ $contactName }}</h2>
                        <span class="noia-badge-neutral py-0.5">{{ $statusLabels[$conversation->status] ?? $conversation->status }}</span>
                    </div>
                    <div class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                        <span>{{ $conversation->contact->primary_phone }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $conversation->channel?->name }}</span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-end">
                @can('messages.send')
                    @if($conversation->assigned_user_id !== auth()->id())
                        <form method="POST" action="{{ route('conversations.assign-me', $conversation) }}">
                            @csrf
                            @method('PUT')
                            <button class="noia-btn-success h-10 w-full px-4 xl:w-auto">Asignar a mi</button>
                        </form>
                    @endif
                @endcan
                <form method="POST" action="{{ route('conversations.assign', $conversation) }}" class="grid gap-2 sm:grid-cols-[minmax(150px,1fr)_minmax(130px,1fr)_auto]">
                    @csrf
                    @method('PUT')
                    <select class="noia-select h-10 bg-white" name="assigned_user_id">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected($conversation->assigned_user_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select class="noia-select h-10 bg-white" name="status">
                        @foreach($statusLabels as $status => $label)
                            <option value="{{ $status }}" @selected($conversation->status === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="noia-btn-primary h-10 px-4">Actualizar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5">
        <div class="mx-auto max-w-4xl space-y-3">
            @php
                $lastDate = null;
                $displayTimezone = config('app.display_timezone', 'America/Bogota');
            @endphp
            @forelse($timeline as $item)
                @php
                    $createdAt = $item->created_at?->copy()->timezone($displayTimezone);
                    $readAt = $item->read_at?->copy()->timezone($displayTimezone);
                    $deliveredAt = $item->delivered_at?->copy()->timezone($displayTimezone);
                    $sentAt = $item->sent_at?->copy()->timezone($displayTimezone);
                    $dateKey = optional($createdAt)->format('Y-m-d');
                    $providerError = $item->provider_logs->first(fn ($log) => $log->hasError());
                    $statusHint = match ($item->status) {
                        'read' => 'Leido'.($readAt ? ' '.$readAt->format('H:i') : ''),
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
                        <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm">
                            {{ optional($createdAt)->format('d/m/Y') }}
                        </span>
                    </div>
                    @php $lastDate = $dateKey; @endphp
                @endif

                <div class="@if($item->direction === 'outbound') ml-auto rounded-br-sm bg-[#d9fdd3] text-slate-950 @else rounded-bl-sm bg-white text-slate-950 @endif max-w-[88%] rounded-lg px-4 py-3 shadow-sm ring-1 ring-black/5 lg:max-w-[72%]">
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
                                    @elseif(in_array($item->status, ['failed', 'bounced', 'blocked_by_policy'], true))
                                        !
                                    @endif
                                </span>
                                @if($item->status === 'read' && $readAt)
                                    <span class="text-slate-400">{{ $readAt->format('H:i') }}</span>
                                @endif
                            @endif
                        </p>
                    @endif

                    @if($item->compliance_block_label)
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            <p class="font-semibold">Envio bloqueado: {{ $item->compliance_block_label }}</p>
                            <p class="mt-1">{{ $item->compliance_block_description }}</p>
                        </div>
                    @endif

                    @if($providerError)
                        <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                            <p class="font-semibold">Error de Meta</p>
                            @if($providerError->errorCode())
                                <p class="mt-1">Código: {{ $providerError->errorCode() }}</p>
                            @endif
                            @if($providerError->errorMessage())
                                <p class="mt-1">{{ $providerError->errorMessage() }}</p>
                            @endif
                            @if($providerError->errorDetails())
                                <p class="mt-1">{{ $providerError->errorDetails() }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-slate-300 bg-white/80 p-6 text-sm text-slate-500">
                    Esta conversación aún no tiene mensajes.
                </div>
            @endforelse
        </div>
    </div>

    <div class="border-t border-slate-200 bg-white px-4 py-4 shadow-[0_-16px_40px_rgba(15,23,42,0.06)]">
        <div class="mx-auto max-w-4xl space-y-3">
            @if($customerCareWindowClosed)
                <div class="flex flex-col gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold">{{ $freeFormEligibility->label() }}</p>
                        <p class="mt-1">{{ $freeFormEligibility->description() }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800">Usar plantilla</span>
                </div>
            @endif

            @if($customerCareWindowClosed)
                <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_310px]">
                    <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-900">Enviar plantilla aprobada</p>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800">{{ $templates->count() }} disponibles</span>
                        </div>
                        <form method="POST" action="{{ route('conversations.reply-template', $conversation) }}" class="mt-3 grid gap-3 lg:grid-cols-[minmax(180px,1fr)_minmax(180px,1fr)_auto]">
                            @csrf
                            <select class="noia-select bg-white" name="message_template_id">
                                <option value="">Selecciona plantilla</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected((int) old('message_template_id') === $template->id)>
                                        {{ $template->name }} · {{ $template->currentVersion?->expectedVariableCount() ?? 0 }} variables
                                    </option>
                                @endforeach
                            </select>
                            <input class="noia-input bg-white" name="variables" value="{{ old('variables') }}" placeholder="Variables separadas por |">
                            <button class="noia-btn-warning px-5">Enviar plantilla</button>
                            @error('variables')
                                <p class="text-xs font-semibold text-red-600 lg:col-span-3">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-amber-900/80 lg:col-span-3">Ejemplo de variables: Juan|12345</p>
                        </form>
                    </div>

                    <details class="rounded-lg border border-slate-200 bg-slate-50/80 p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-700">Texto y adjuntos pausados</summary>
                        <div class="mt-3 space-y-2 text-sm text-slate-500">
                            <p>Se habilitan cuando el cliente responde por WhatsApp y abre la ventana de 24 horas.</p>
                            <textarea class="noia-textarea min-h-[54px] resize-none bg-white" rows="2" placeholder="Responder conversación" disabled></textarea>
                        </div>
                    </details>
                </div>
            @else
                <div class="grid gap-3 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
                    <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Respuesta rápida</p>
                        <span class="text-xs font-semibold text-emerald-700">Ventana 24h activa</span>
                    </div>

                    <form method="POST" action="{{ route('conversations.reply', $conversation) }}" class="space-y-2">
                        @csrf
                        <h3 class="sr-only">Respuesta de texto</h3>
                        <div class="grid gap-2 lg:grid-cols-[1fr_auto]">
                            <textarea class="noia-textarea min-h-[56px] resize-none bg-white" name="body" rows="2" placeholder="Responder conversación"></textarea>
                            <button class="noia-btn-success h-full min-h-[56px] px-6">Enviar</button>
                        </div>
                    </form>

                    <details class="rounded-lg border border-slate-200 bg-white p-3">
                        <summary class="cursor-pointer text-sm font-semibold text-slate-800">Adjuntar imagen o documento</summary>
                        <form method="POST" enctype="multipart/form-data" action="{{ route('conversations.reply-media', $conversation) }}" class="mt-3 grid gap-3 lg:grid-cols-[160px_1fr]">
                            @csrf
                            <select class="noia-select bg-white" name="type">
                                <option value="image">Imagen</option>
                                <option value="document">Documento</option>
                            </select>
                            <input class="noia-file-input bg-white" type="file" name="file">
                            <textarea class="noia-textarea min-h-[72px] bg-white lg:col-span-2" name="body" rows="2" placeholder="Texto opcional"></textarea>
                            <button class="noia-btn-info lg:col-span-2">Enviar adjunto</button>
                        </form>
                    </details>
                </div>

                <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-slate-900">Respuesta con plantilla</p>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-800">{{ $templates->count() }} disponibles</span>
                    </div>
                    <form method="POST" action="{{ route('conversations.reply-template', $conversation) }}" class="mt-3 space-y-3">
                        @csrf
                        <select class="noia-select w-full bg-white" name="message_template_id">
                            <option value="">Selecciona plantilla</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" @selected((int) old('message_template_id') === $template->id)>
                                    {{ $template->name }} · {{ $template->currentVersion?->expectedVariableCount() ?? 0 }} variables
                                </option>
                            @endforeach
                        </select>
                        <input class="noia-input w-full bg-white" name="variables" value="{{ old('variables') }}" placeholder="Variables separadas por |">
                        @error('variables')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        <button class="noia-btn-warning w-full">Enviar plantilla</button>
                        <p class="text-xs text-amber-900/80">Completa exactamente la cantidad de variables indicada. Ejemplo: Juan|12345</p>
                    </form>
                </div>
                </div>
            @endif
        </div>
    </div>
</section>
