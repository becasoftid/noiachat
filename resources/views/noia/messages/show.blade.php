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
                            <a href="{{ asset('storage/'.$attachment->mediaFile->path) }}" target="_blank" class="underline underline-offset-2">
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
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach($message->providerLogs as $log)
                        <li>{{ $providerEventLabels[$log->event_type] ?? $log->event_type }} · {{ $log->created_at->format('Y-m-d H:i:s') }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.noia>
