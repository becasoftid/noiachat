@php
    $activeConversationId = $activeConversationId ?? null;
    $displayTimezone = config('app.display_timezone', 'America/Bogota');
@endphp

<div class="space-y-3 p-3">
    @forelse($conversations as $listConversation)
        @php
            $name = $listConversation->contact->full_name;
            $initials = collect(explode(' ', trim($name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->join('');
            $isActive = (string) $activeConversationId === (string) $listConversation->id;
            $latestInboundAt = $listConversation->latest_inbound_created_at
                ? \Illuminate\Support\Carbon::parse($listConversation->latest_inbound_created_at)->timezone($displayTimezone)
                : null;
            $latestOutboundAt = $listConversation->latest_outbound_created_at
                ? \Illuminate\Support\Carbon::parse($listConversation->latest_outbound_created_at)->timezone($displayTimezone)
                : null;
            $latestIsOutbound = $latestOutboundAt && (! $latestInboundAt || $latestOutboundAt->gte($latestInboundAt));
            $preview = $latestIsOutbound ? $listConversation->latest_outbound_body : $listConversation->latest_inbound_body;
            $preview = $preview ?: $listConversation->contact->primary_phone;
            $previewPrefix = $latestIsOutbound ? 'Tu: ' : '';
            $timestamp = $listConversation->last_message_at?->copy()->timezone($displayTimezone);
            $windowClosed = ! $latestInboundAt || $latestInboundAt->copy()->addHours(24)->isPast();
        @endphp
        <a
            href="{{ route('conversations.index', array_merge(request()->query(), ['conversation' => $listConversation->id])) }}"
            data-unread-count="{{ $listConversation->unread_count }}"
            class="@class([
                'block rounded-lg border p-3 text-left transition focus:outline-none focus:ring-4 focus:ring-emerald-100',
                'border-emerald-200 bg-emerald-50/80 shadow-sm' => $isActive,
                'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm' => ! $isActive,
            ])"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white shadow-sm">
                    {{ $initials ?: 'N' }}
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-bold text-slate-950">{{ $name }}</p>
                            <p class="mt-0.5 truncate text-xs font-semibold text-slate-500">
                                {{ $listConversation->contact->primary_phone }} · {{ $listConversation->channel?->name ?? 'Canal' }}
                            </p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="@class(['text-xs font-semibold', 'text-emerald-700' => $listConversation->unread_count > 0, 'text-slate-400' => $listConversation->unread_count < 1])">
                                @if($timestamp)
                                    {{ $timestamp->isToday() ? $timestamp->format('H:i') : ($timestamp->isYesterday() ? 'Ayer' : $timestamp->format('d/m')) }}
                                @else
                                    --
                                @endif
                            </p>
                            @if($listConversation->unread_count > 0)
                                <span class="sr-only">{{ $listConversation->unread_count }} sin leer</span>
                                <span class="mt-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-600 px-1.5 text-xs font-bold text-white">
                                    {{ $listConversation->unread_count }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <p class="mt-2 truncate text-sm text-slate-700">{{ $previewPrefix }}{{ $preview }}</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="@class(['rounded-full px-2.5 py-1', 'bg-amber-100 text-amber-800' => $windowClosed, 'bg-emerald-100 text-emerald-700' => ! $windowClosed])">
                            {{ $windowClosed ? 'Ventana cerrada' : 'Ventana abierta' }}
                        </span>
                        <span class="truncate text-slate-500">{{ $listConversation->assignedUser?->name ?? 'Sin asignar' }}</span>
                    </div>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
            <p class="font-semibold text-slate-700">No hay conversaciones para mostrar.</p>
            <p class="mt-1">Ajusta los filtros o inicia un nuevo chat.</p>
        </div>
    @endforelse
</div>

<div class="border-t border-slate-100 bg-white px-3 py-3">{{ $conversations->appends(request()->query())->links() }}</div>
