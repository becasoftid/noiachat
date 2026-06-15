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
            $latestInbound = $listConversation->latestInboundMessage;
            $latestOutbound = $listConversation->latestMessage;
            $latestItem = collect([$latestInbound, $latestOutbound])
                ->filter()
                ->sortByDesc('created_at')
                ->first();
            $latestIsOutbound = $latestItem && $latestOutbound && $latestItem->is($latestOutbound);
            $preview = $latestItem?->body ?: $listConversation->contact->primary_phone;
            $previewPrefix = $latestIsOutbound ? 'Tu: ' : '';
            $timestamp = $listConversation->last_message_at;
        @endphp
        <a
            href="{{ route('conversations.index', array_merge(request()->query(), ['conversation' => $listConversation->id])) }}"
            class="@class([
                'group grid min-h-[72px] grid-cols-[48px_minmax(0,1fr)_64px] items-center gap-3 px-4 py-2.5 transition',
                'bg-slate-100' => $isActive,
                'bg-white hover:bg-slate-50' => ! $isActive,
            ])"
        >
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white shadow-sm">
                {{ $initials ?: 'N' }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-[15px] font-semibold leading-5 text-slate-950">{{ $name }}</p>
                <span class="sr-only">{{ $listConversation->contact->primary_phone }}</span>
                <p class="mt-0.5 truncate text-sm leading-5 text-slate-500">{{ $previewPrefix }}{{ $preview }}</p>
            </div>

            <div class="flex h-full min-w-[64px] flex-col items-end justify-center gap-1 text-right">
                <p class="@class([
                    'text-xs font-medium',
                    'text-emerald-600' => $listConversation->unread_count > 0,
                    'text-slate-400' => $listConversation->unread_count < 1,
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
