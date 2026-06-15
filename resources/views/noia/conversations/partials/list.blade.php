@php
    $activeConversationId = $activeConversationId ?? null;
@endphp

<div class="divide-y divide-slate-100">
    @forelse($conversations as $conversation)
        @php
            $name = $conversation->contact->full_name;
            $initials = collect(explode(' ', trim($name)))
                ->filter()
                ->take(2)
                ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->join('');
            $isActive = (string) $activeConversationId === (string) $conversation->id;
        @endphp
        <a
            href="{{ route('conversations.show', $conversation) }}"
            class="@class([
                'group flex items-center gap-3 px-4 py-3 transition hover:bg-slate-50',
                'bg-cyan-50/80' => $isActive,
            ])"
        >
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#10202a] text-sm font-bold text-white">
                {{ $initials ?: 'N' }}
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-start justify-between gap-3">
                    <p class="truncate text-sm font-semibold text-slate-950">{{ $name }}</p>
                    <p class="shrink-0 text-xs text-slate-400">
                        {{ optional($conversation->last_message_at)->format('H:i') ?? '--' }}
                    </p>
                </div>
                <div class="mt-1 flex items-center justify-between gap-3">
                    <p class="truncate text-sm text-slate-500">
                        {{ $conversation->contact->primary_phone }} · {{ $conversation->assignedUser?->name ?? 'Sin asignar' }}
                    </p>
                    @if($conversation->unread_count > 0)
                        <span class="sr-only">{{ $conversation->unread_count }} sin leer</span>
                        <span aria-hidden="true" class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-rose-600 px-1.5 text-xs font-bold text-white">
                            {{ $conversation->unread_count }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 truncate text-xs font-medium text-slate-400">
                    {{ $statusLabels[$conversation->status] ?? $conversation->status }}
                    @if($conversation->last_message_at)
                        · {{ $conversation->last_message_at->format('Y-m-d') }}
                    @endif
                </p>
            </div>
        </a>
    @empty
        <div class="m-4 rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-6 text-sm text-slate-500">
            No hay conversaciones para mostrar.
        </div>
    @endforelse
</div>

<div class="border-t border-slate-100 px-4 py-3">{{ $conversations->appends(request()->query())->links() }}</div>
