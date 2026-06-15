@php
    $activeConversationId = $activeConversationId ?? null;
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
                ? \Illuminate\Support\Carbon::parse($listConversation->latest_inbound_created_at)
                : null;
            $latestOutboundAt = $listConversation->latest_outbound_created_at
                ? \Illuminate\Support\Carbon::parse($listConversation->latest_outbound_created_at)
                : null;
            $latestIsOutbound = $latestOutboundAt && (! $latestInboundAt || $latestOutboundAt->gte($latestInboundAt));
            $preview = $latestIsOutbound ? $listConversation->latest_outbound_body : $listConversation->latest_inbound_body;
            $preview = $preview ?: $listConversation->contact->primary_phone;
            $previewPrefix = $latestIsOutbound ? 'Tu: ' : '';
            $timestamp = $listConversation->last_message_at;
        @endphp
        <a
            href="{{ route('conversations.index', array_merge(request()->query(), ['conversation' => $listConversation->id])) }}"
            class="@class([
                'group transition hover:bg-slate-50',
                'bg-slate-100' => $isActive,
                'bg-white' => ! $isActive,
            ])"
            style="display:grid;grid-template-columns:42px minmax(0,1fr) 52px;align-items:center;column-gap:10px;width:100%;max-width:100%;min-height:64px;overflow:hidden;padding:10px 14px;text-decoration:none;"
        >
            <div style="display:flex;width:42px;height:42px;align-items:center;justify-content:center;border-radius:9999px;background:#10202a;color:#fff;font-size:13px;font-weight:700;line-height:1;box-shadow:0 1px 2px rgb(15 23 42 / 0.08);">
                {{ $initials ?: 'N' }}
            </div>

            <div style="min-width:0;overflow:hidden;">
                <p style="display:block;margin:0;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#0f172a;font-size:15px;font-weight:700;line-height:20px;">{{ $name }}</p>
                <span class="sr-only">{{ $listConversation->contact->primary_phone }}</span>
                <p style="display:block;margin:2px 0 0;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#64748b;font-size:13px;line-height:18px;">{{ $previewPrefix }}{{ $preview }}</p>
            </div>

            <div style="display:flex;width:52px;min-width:52px;flex-direction:column;align-items:flex-end;justify-content:center;gap:4px;text-align:right;">
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
