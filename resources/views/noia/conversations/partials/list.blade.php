@php
    $activeConversationId = $activeConversationId ?? null;
    $displayTimezone = config('app.display_timezone', 'America/Bogota');
@endphp

<div class="divide-y divide-slate-100">
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
        @endphp
        <a
            href="{{ route('conversations.index', array_merge(request()->query(), ['conversation' => $listConversation->id])) }}"
            data-unread-count="{{ $listConversation->unread_count }}"
            class="@class([
                'group relative transition hover:bg-slate-50',
                'bg-cyan-50/70' => $isActive,
                'bg-white' => ! $isActive,
            ])"
            style="display:grid;grid-template-columns:42px minmax(0,1fr) 58px;align-items:center;column-gap:10px;width:100%;max-width:100%;min-height:72px;overflow:hidden;padding:12px 14px;text-decoration:none;"
        >
            @if($isActive)
                <span class="absolute inset-y-3 left-0 w-1 rounded-r-full bg-cyan-700" aria-hidden="true"></span>
            @endif

            <div style="display:flex;width:42px;height:42px;align-items:center;justify-content:center;border-radius:9999px;background:#10202a;color:#fff;font-size:13px;font-weight:700;line-height:1;box-shadow:0 1px 2px rgb(15 23 42 / 0.08);">
                {{ $initials ?: 'N' }}
            </div>

            <div style="min-width:0;overflow:hidden;">
                <div class="flex min-w-0 items-center gap-2">
                    <p style="display:block;margin:0;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;font-size:15px;font-weight:700;line-height:20px;">{{ $name }}</p>
                    @if($listConversation->status !== 'open')
                        <span class="hidden shrink-0 rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500 sm:inline-flex">
                            {{ $statusLabels[$listConversation->status] ?? $listConversation->status }}
                        </span>
                    @endif
                </div>
                <span class="sr-only">{{ $listConversation->contact->primary_phone }}</span>
                <p style="display:block;margin:2px 0 0;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:13px;line-height:18px;">{{ $previewPrefix }}{{ $preview }}</p>
                <p class="mt-1 truncate text-[11px] font-semibold text-slate-400">{{ $listConversation->channel?->name }} · {{ $listConversation->assignedUser?->name ?? 'Sin asignar' }}</p>
            </div>

            <div style="display:flex;width:58px;min-width:58px;flex-direction:column;align-items:flex-end;justify-content:center;gap:4px;text-align:right;">
                <p style="margin:0;color:{{ $listConversation->unread_count > 0 ? '#059669' : '#94a3b8' }};font-size:12px;font-weight:600;line-height:16px;white-space:nowrap;">
                    @if($timestamp)
                        {{ $timestamp->isToday() ? $timestamp->format('H:i') : $timestamp->format('d/m/Y') }}
                    @else
                        --
                    @endif
                </p>
                @if($listConversation->unread_count > 0)
                    <span class="sr-only">{{ $listConversation->unread_count }} sin leer</span>
                    <span aria-hidden="true" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-emerald-500 px-1.5 text-xs font-bold text-white">
                        {{ $listConversation->unread_count }}
                    </span>
                @endif
            </div>
        </a>
    @empty
        <div class="m-3 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-6 text-sm text-slate-500">
            No hay conversaciones para mostrar.
        </div>
    @endforelse
</div>

<div class="border-t border-slate-100 px-3 py-3">{{ $conversations->appends(request()->query())->links() }}</div>
