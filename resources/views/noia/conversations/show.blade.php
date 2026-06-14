<x-layouts.noia title="Conversación" header="Conversación">
    @php
        $statusLabels = [
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
        ];
        $messageStatusLabels = [
            'draft' => 'Borrador',
            'queued' => 'En cola',
            'sending' => 'Enviando',
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'read' => 'Leído',
            'failed' => 'Fallido',
            'bounced' => 'Rebotado',
            'cancelled' => 'Cancelado',
            'blocked_by_policy' => 'Bloqueado por política',
        ];
        $customerCareWindowClosed = $freeFormEligibility->value === 'blocked_customer_care_window';
    @endphp
    <div class="grid gap-6 lg:grid-cols-[320px_1fr]">
        <aside class="space-y-6">
            <div class="noia-card">
                <h3 class="font-semibold">Gestión</h3>
                @can('messages.send')
                    @if($conversation->assigned_user_id !== auth()->id())
                        <form method="POST" action="{{ route('conversations.assign-me', $conversation) }}" class="mt-4">
                            @csrf
                            @method('PUT')
                            <button class="noia-btn-success w-full">Asignar a mi</button>
                        </form>
                    @endif
                @endcan
                <form method="POST" action="{{ route('conversations.assign', $conversation) }}" class="mt-4 space-y-3">
                    @csrf
                    @method('PUT')
                    <select class="noia-select w-full" name="assigned_user_id">
                        <option value="">Sin asignar</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected($conversation->assigned_user_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <select class="noia-select w-full" name="status">
                        @foreach($statusLabels as $status => $label)
                            <option value="{{ $status }}" @selected($conversation->status === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="noia-btn-primary">Actualizar</button>
                </form>
                <div class="mt-4 text-sm text-slate-600">
                    <p>Contacto: {{ $conversation->contact->full_name }}</p>
                    <p>Canal: {{ $conversation->channel?->name }}</p>
                    <p>Asignado: {{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</p>
                </div>
            </div>
        </aside>

        <div class="noia-card">
            <div class="space-y-4">
                @forelse($timeline as $item)
                    <div class="@if($item->direction === 'outbound') ml-auto bg-[#10202a] text-white @else bg-slate-100 text-slate-900 @endif max-w-xl rounded-lg px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-medium uppercase tracking-[0.2em] opacity-70">{{ $item->direction === 'outbound' ? 'Saliente' : 'Entrante' }}</p>
                            <p class="text-xs opacity-70">{{ optional($item->created_at)->format('Y-m-d H:i') }}</p>
                        </div>

                        @if(filled($item->body))
                            <p class="mt-2 text-sm">{{ $item->body }}</p>
                        @endif

                        @if($item->attachments->isNotEmpty())
                            <div class="mt-3 space-y-2 rounded-lg bg-black/10 p-3 text-sm">
                                @foreach($item->attachments as $attachment)
                                    <a href="{{ asset('storage/'.$attachment->mediaFile->path) }}" target="_blank" class="block noia-link">
                                        {{ $attachment->mediaFile->original_name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($item->status)
                            <p class="mt-2 text-xs opacity-70">Estado: {{ $messageStatusLabels[$item->status] ?? $item->status }}</p>
                        @endif

                        @if($item->compliance_block_label)
                            <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                <p class="font-semibold">Envio bloqueado: {{ $item->compliance_block_label }}</p>
                                <p class="mt-1">{{ $item->compliance_block_description }}</p>
                            </div>
                        @endif

                        @php
                            $providerError = $item->provider_logs->first(fn ($log) => $log->hasError());
                        @endphp
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
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-6 text-sm text-slate-500">
                        Esta conversación aún no tiene mensajes.
                    </div>
                @endforelse
            </div>

            <div class="mt-6 grid gap-6 border-t border-slate-200 pt-4 lg:grid-cols-2">
                @if($customerCareWindowClosed)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 lg:col-span-2">
                        <p class="font-semibold">{{ $freeFormEligibility->label() }}</p>
                        <p class="mt-1">{{ $freeFormEligibility->description() }}</p>
                    </div>
                @endif
                <form method="POST" action="{{ route('conversations.reply', $conversation) }}" class="space-y-3">
                    @csrf
                    <h3 class="font-semibold">Respuesta de texto</h3>
                    <textarea class="noia-textarea" name="body" rows="4" placeholder="Responder conversación" @disabled($customerCareWindowClosed)></textarea>
                    <button class="noia-btn-success" @disabled($customerCareWindowClosed)>Enviar respuesta</button>
                </form>

                <form method="POST" enctype="multipart/form-data" action="{{ route('conversations.reply-media', $conversation) }}" class="space-y-3">
                    @csrf
                    <h3 class="font-semibold">Respuesta con adjunto</h3>
                    <select class="noia-select w-full" name="type" @disabled($customerCareWindowClosed)>
                        <option value="image">Imagen</option>
                        <option value="document">Documento</option>
                    </select>
                    <textarea class="noia-textarea min-h-[96px]" name="body" rows="2" placeholder="Texto opcional" @disabled($customerCareWindowClosed)></textarea>
                    <input class="noia-file-input" type="file" name="file" @disabled($customerCareWindowClosed)>
                    <button class="noia-btn-info" @disabled($customerCareWindowClosed)>Enviar adjunto</button>
                </form>
                <form method="POST" action="{{ route('conversations.reply-template', $conversation) }}" class="space-y-3 lg:col-span-2">
                    @csrf
                    <h3 class="font-semibold">Respuesta con plantilla</h3>
                    <div class="grid gap-3 lg:grid-cols-[1fr_1fr_auto]">
                        <select class="noia-select w-full" name="message_template_id">
                            <option value="">Selecciona plantilla</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" @selected((int) old('message_template_id') === $template->id)>
                                    {{ $template->name }} · {{ $template->currentVersion?->expectedVariableCount() ?? 0 }} variables
                                </option>
                            @endforeach
                        </select>
                        <input class="noia-input w-full" name="variables" value="{{ old('variables') }}" placeholder="Variables separadas por |">
                        <button class="noia-btn-warning">Enviar plantilla</button>
                    </div>
                    @error('variables')
                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-slate-500">Completa exactamente la cantidad de variables indicada. Ejemplo: Juan|12345</p>
                </form>
            </div>
        </div>
    </div>
</x-layouts.noia>
