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
                'noia-conversation-row group',
                'is-active' => $isActive,
            ])"
        >
            <div class="noia-conversation-avatar">
                {{ $initials ?: 'N' }}
            </div>

            <div class="noia-conversation-main">
                <p class="noia-conversation-name">{{ $name }}</p>
                <span class="sr-only">{{ $listConversation->contact->primary_phone }}</span>
                <p class="noia-conversation-preview">{{ $previewPrefix }}{{ $preview }}</p>
            </div>

            <div class="noia-conversation-meta">
                <p class="@class([
                    'noia-conversation-time',
                    'has-unread' => $listConversation->unread_count > 0,
                ])">
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
