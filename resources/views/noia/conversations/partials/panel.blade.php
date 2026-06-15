<section class="flex min-h-[720px] flex-col bg-[#eef5f7] lg:min-h-0">
    <div class="border-b border-slate-200 bg-white px-4 py-3">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                @php
                    $contactName = $conversation->contact->full_name;
                    $contactInitials = collect(explode(' ', trim($contactName)))
                        ->filter()
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                        ->join('');
                @endphp
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white">
                    {{ $contactInitials ?: 'N' }}
                </div>
                <div class="min-w-0">
                    <h2 class="truncate text-base font-semibold text-slate-950">{{ $contactName }}</h2>
                    <p class="truncate text-xs text-slate-500">
                        {{ $conversation->contact->primary_phone }} · {{ $conversation->channel?->name }} · {{ $conversation->assignedUser?->name ?? 'Sin asignar' }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
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
        <div class="mx-auto max-w-5xl space-y-3">
            @php
                $lastDate = null;
                $displayTimezone = config('app.display_timezone', 'America/Bogota');
            @endphp
            @forelse($timeline as $item)
                @php
                    $createdAt = $item->created_at?->copy()->timezone($displayTimezone);
                    $dateKey = optional($createdAt)->format('Y-m-d');
                    $providerError = $item->provider_logs->first(fn ($log) => $log->hasError());
                @endphp

                @if($dateKey && $dateKey !== $lastDate)
                    <div class="flex justify-center py-2">
                        <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-500 shadow-sm">
                            {{ optional($createdAt)->format('d/m/Y') }}
                        </span>
                    </div>
                    @php $lastDate = $dateKey; @endphp
                @endif

                <div class="@if($item->direction === 'outbound') ml-auto bg-[#d9fdd3] text-slate-950 @else bg-white text-slate-950 @endif max-w-[82%] rounded-lg px-4 py-3 shadow-sm lg:max-w-[68%]">
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
                        <p class="mt-2 text-xs text-slate-500">Estado: {{ $messageStatusLabels[$item->status] ?? $item->status }}</p>
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

    <div class="border-t border-slate-200 bg-white px-4 py-3">
        <div class="mx-auto max-w-5xl space-y-3">
            @if($customerCareWindowClosed)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">{{ $freeFormEligibility->label() }}</p>
                    <p class="mt-1">{{ $freeFormEligibility->description() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('conversations.reply', $conversation) }}" class="space-y-2">
                @csrf
                <h3 class="sr-only">Respuesta de texto</h3>
                <div class="grid gap-2 lg:grid-cols-[1fr_auto]">
                    <textarea class="noia-textarea min-h-[54px] resize-none bg-slate-50" name="body" rows="2" placeholder="Responder conversación" @disabled($customerCareWindowClosed)></textarea>
                    <button class="noia-btn-success h-full min-h-[54px] px-6" @disabled($customerCareWindowClosed)>Enviar respuesta</button>
                </div>
            </form>

            <div class="grid gap-3 xl:grid-cols-2">
                <details class="rounded-lg border border-slate-200 bg-slate-50/80 p-3">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">Respuesta con adjunto</summary>
                    <form method="POST" enctype="multipart/form-data" action="{{ route('conversations.reply-media', $conversation) }}" class="mt-3 space-y-3">
                        @csrf
                        <select class="noia-select w-full bg-white" name="type" @disabled($customerCareWindowClosed)>
                            <option value="image">Imagen</option>
                            <option value="document">Documento</option>
                        </select>
                        <textarea class="noia-textarea min-h-[80px] bg-white" name="body" rows="2" placeholder="Texto opcional" @disabled($customerCareWindowClosed)></textarea>
                        <input class="noia-file-input bg-white" type="file" name="file" @disabled($customerCareWindowClosed)>
                        <button class="noia-btn-info" @disabled($customerCareWindowClosed)>Enviar adjunto</button>
                    </form>
                </details>

                <details class="rounded-lg border border-slate-200 bg-slate-50/80 p-3" open>
                    <summary class="cursor-pointer text-sm font-semibold text-slate-800">Respuesta con plantilla</summary>
                    <form method="POST" action="{{ route('conversations.reply-template', $conversation) }}" class="mt-3 space-y-3">
                        @csrf
                        <div class="grid gap-3 lg:grid-cols-[1fr_1fr_auto]">
                            <select class="noia-select w-full bg-white" name="message_template_id">
                                <option value="">Selecciona plantilla</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" @selected((int) old('message_template_id') === $template->id)>
                                        {{ $template->name }} · {{ $template->currentVersion?->expectedVariableCount() ?? 0 }} variables
                                    </option>
                                @endforeach
                            </select>
                            <input class="noia-input w-full bg-white" name="variables" value="{{ old('variables') }}" placeholder="Variables separadas por |">
                            <button class="noia-btn-warning">Enviar plantilla</button>
                        </div>
                        @error('variables')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-slate-500">Completa exactamente la cantidad de variables indicada. Ejemplo: Juan|12345</p>
                    </form>
                </details>
            </div>
        </div>
    </div>
</section>
