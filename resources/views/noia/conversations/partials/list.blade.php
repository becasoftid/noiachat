<div class="space-y-3">
    @forelse($conversations as $conversation)
        <a href="{{ route('conversations.show', $conversation) }}" class="noia-card block p-4 transition hover:-translate-y-0.5 hover:border-cyan-200">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold">{{ $conversation->contact->full_name }}</p>
                        @if($conversation->unread_count > 0)
                            <span class="noia-badge-danger">{{ $conversation->unread_count }} sin leer</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-500">{{ $conversation->contact->primary_phone }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm">{{ $statusLabels[$conversation->status] ?? $conversation->status }}</p>
                    <p class="text-xs text-slate-400">{{ $conversation->assignedUser?->name ?? 'Sin asignar' }}</p>
                    <p class="text-xs text-slate-400">{{ optional($conversation->last_message_at)->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50/70 p-6 text-sm text-slate-500">
            No hay conversaciones para mostrar.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $conversations->appends(request()->query())->links() }}</div>
