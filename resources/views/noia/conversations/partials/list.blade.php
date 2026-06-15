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
        @endphp
        <a
            href="{{ route('conversations.index', array_merge(request()->query(), ['conversation' => $listConversation->id])) }}"
            class="@class([
                'group grid min-h-[72px] grid-cols-[42px_minmax(0,1fr)_48px] items-center gap-2 px-4 py-3 transition',
                'bg-cyan-50/80' => $isActive,
                'bg-white hover:bg-slate-50' => ! $isActive,
            ])"
        >
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-xs font-bold text-white shadow-sm">
                {{ $initials ?: 'N' }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold leading-5 text-slate-950">{{ $name }}</p>
                <p class="truncate text-xs leading-5 text-slate-500">{{ $listConversation->contact->primary_phone }}</p>
                <div class="flex min-w-0 items-center gap-1.5 text-[11px] font-medium text-slate-400">
                    <span class="truncate">{{ $statusLabels[$listConversation->status] ?? $listConversation->status }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="truncate">{{ $listConversation->assignedUser?->name ?? 'Sin asignar' }}</span>
                </div>
            </div>

            <div class="flex h-full min-w-[48px] flex-col items-end justify-between gap-1 text-right">
                <p class="text-xs font-medium text-slate-400">
                    {{ optional($listConversation->last_message_at)->format('H:i') ?? '--' }}
                </p>
                @if($listConversation->unread_count > 0)
                    <span class="sr-only">{{ $listConversation->unread_count }} sin leer</span>
                    <span aria-hidden="true" class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1.5 text-xs font-bold text-white">
                        {{ $listConversation->unread_count }}
                    </span>
                @endif
                @if($listConversation->last_message_at)
                    <p class="text-[11px] font-medium text-slate-300">{{ $listConversation->last_message_at->format('d/m') }}</p>
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
