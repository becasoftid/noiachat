<x-layouts.noia title="Detalle Mensaje" header="Detalle de mensaje">
    @php
        $typeLabels = [
            'text' => 'Texto',
            'image' => 'Imagen',
            'document' => 'Documento',
            'template' => 'Plantilla',
        ];
        $statusLabels = [
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
        $eventTypeLabels = [
            'message_created' => 'Mensaje creado',
            'attachment_added' => 'Adjunto agregado',
            'provider_sent' => 'Enviado al proveedor',
            'webhook_status' => 'Estado recibido por webhook',
            'job_failed' => 'Error en el job',
            'retry_requested' => 'Reintento solicitado',
            'sending' => 'Envío en proceso',
        ];
        $providerEventLabels = [
            'send_text' => 'Envío de texto',
            'send_text_failed' => 'Error al enviar texto',
            'send_image' => 'Envío de imagen',
            'send_document' => 'Envío de documento',
            'send_template' => 'Envío de plantilla',
            'send_template_failed' => 'Error al enviar plantilla',
            'webhook' => 'Webhook recibido',
        ];
    @endphp
    <div class="grid gap-6 lg:grid-cols-[1fr_.9fr]">
        <div class="noia-card">
            <h3 class="text-lg font-semibold">{{ $message->contact->full_name }}</h3>
            <p class="mt-2 text-sm">{{ $typeLabels[$message->type] ?? $message->type }} · {{ $statusLabels[$message->status] ?? $message->status }}</p>
            @if($message->complianceBlockLabel())
                <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p class="font-semibold">Envio bloqueado: {{ $message->complianceBlockLabel() }}</p>
                    <p class="mt-1">{{ $message->complianceBlockDescription() }}</p>
                </div>
            @endif

            @if(filled($message->body))
                <p class="mt-4 text-sm text-slate-700">{{ $message->body }}</p>
            @endif

            <div class="mt-6">
                @if(in_array($message->status, ['failed', 'bounced', 'blocked_by_policy'], true))
                    <form method="POST" action="{{ route('messages.retry', $message) }}" class="mb-4">
                        @csrf
                        <button class="noia-btn-warning">Reintentar envío</button>
                    </form>
                @endif
                <h4 class="font-semibold">Eventos</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($message->events as $event)
                        <li>{{ $statusLabels[$event->status] ?? $event->status }} · {{ $eventTypeLabels[$event->event_type] ?? $event->event_type ?? 'Evento' }} · {{ optional($event->occurred_at)->format('Y-m-d H:i:s') }}</li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <div class="noia-card">
                <h4 class="font-semibold">Adjuntos</h4>
                <ul class="mt-3 space-y-2 text-sm">
                    @forelse($message->attachments as $attachment)
                        <li>
                            <a href="{{ asset('storage/'.$attachment->mediaFile->path) }}" target="_blank" class="noia-link">
                                {{ $attachment->mediaFile->original_name }}
                            </a>
                            · {{ $attachment->mediaFile->mime_type }}
                        </li>
                    @empty
                        <li class="text-slate-500">Sin adjuntos</li>
                    @endforelse
                </ul>
            </div>

            <div class="noia-card">
                <h4 class="font-semibold">Provider logs</h4>
                <ul class="mt-3 space-y-3 text-sm">
                    @forelse($message->providerLogs as $log)
                        <li class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-medium">{{ $providerEventLabels[$log->event_type] ?? $log->event_type }}</span>
                                <span class="text-xs text-slate-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                            </div>

                            @if($log->hasError())
                                <div class="mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-red-800">
                                    <p class="font-semibold">Error de Meta</p>
                                    @if($log->errorCode())
                                        <p class="mt-1">Código: {{ $log->errorCode() }}</p>
                                    @endif
                                    @if($log->errorMessage())
                                        <p class="mt-1">{{ $log->errorMessage() }}</p>
                                    @endif
                                    @if($log->errorDetails())
                                        <p class="mt-1">{{ $log->errorDetails() }}</p>
                                    @endif
                                </div>
                            @endif

                            @if(filled($log->external_event_id))
                                <p class="mt-2 text-xs text-slate-500">ID proveedor: {{ $log->external_event_id }}</p>
                            @endif

                            @if(filled($log->payload))
                                <details class="mt-3">
                                    <summary class="cursor-pointer text-xs font-medium text-slate-500">Ver payload técnico</summary>
                                    <pre class="mt-2 max-h-80 overflow-auto rounded-md bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </li>
                    @empty
                        <li class="text-slate-500">Sin logs del proveedor</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.noia>
