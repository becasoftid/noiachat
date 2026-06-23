<x-layouts.conversation-workspace title="Conversación">
    @php
        $statusLabels = [
            'open' => 'Abierta',
            'pending' => 'Pendiente',
            'resolved' => 'Resuelta',
            'closed' => 'Cerrada',
        ];
        $messageStatusLabels = [
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
        $customerCareWindowClosed = $freeFormEligibility->value === 'blocked_customer_care_window';
    @endphp
    <div
        class="h-full min-h-0 w-full max-w-full overflow-hidden bg-white lg:grid"
        :class="detailsOpen ? 'xl:grid-cols-[390px_minmax(0,1fr)_320px]' : 'xl:grid-cols-[390px_minmax(0,1fr)]'"
        x-data="{ detailsOpen: window.matchMedia('(min-width: 1280px)').matches }"
    >
        <aside class="hidden min-h-0 flex-col border-slate-200 bg-white lg:flex lg:border-r">
            <div class="border-b border-slate-200 bg-slate-50/80 p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700">Inbox</p>
                        <h2 class="text-lg font-semibold text-slate-950">Chats</h2>
                    </div>
                    <a href="{{ route('conversations.index') }}" class="noia-btn-secondary h-9 px-3">Ver todo</a>
                </div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto">
                @include('noia.conversations.partials.list', [
                    'conversations' => $sideConversations,
                    'statusLabels' => $statusLabels,
                    'activeConversationId' => $conversation->id,
                ])
            </div>
        </aside>

        @include('noia.conversations.partials.panel')
    </div>
</x-layouts.conversation-workspace>
