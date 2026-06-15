@php
    $activeConversationId = $activeConversationId ?? null;
@endphp

<div class="space-y-2 p-3">
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
                'group grid min-h-[82px] grid-cols-[48px_minmax(0,1fr)_auto] items-center gap-3 rounded-lg border px-3 py-3 transition',
                'border-cyan-200 bg-cyan-50/80 shadow-sm' => $isActive,
                'border-slate-100 bg-white hover:border-cyan-100 hover:bg-slate-50' => ! $isActive,
            ])"
        >
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white shadow-sm">
                {{ $initials ?: 'N' }}
            </div>

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold leading-5 text-slate-950">{{ $name }}</p>
                <p class="mt-0.5 truncate text-sm leading-5 text-slate-500">{{ $listConversation->contact->primary_phone }}</p>
                <div class="mt-1 flex min-w-0 items-center gap-1.5 text-xs font-medium text-slate-400">
                    <span class="truncate">{{ $statusLabels[$listConversation->status] ?? $listConversation->status }}</span>
                    <span aria-hidden="true">·</span>
                    <span class="truncate">{{ $listConversation->assignedUser?->name ?? 'Sin asignar' }}</span>
                </div>
            </div>

            <div class="flex h-full min-w-[54px] flex-col items-end justify-between gap-2 text-right">
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
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-6 text-sm text-slate-500">
            No hay conversaciones para mostrar.
        </div>
    @endforelse
</div>

<div class="border-t border-slate-100 px-3 py-3">{{ $conversations->appends(request()->query())->links() }}</div>
