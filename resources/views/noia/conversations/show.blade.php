<x-layouts.noia title="Conversación" header="Conversación">
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
        class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.08)] lg:grid lg:h-[calc(100vh-190px)] lg:min-h-[720px] xl:grid-cols-[390px_minmax(0,1fr)_320px]"
        x-data="{ detailsOpen: false }"
    >
        <aside class="flex min-h-[520px] flex-col border-slate-200 bg-white lg:min-h-0 lg:border-r">
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
</x-layouts.noia>
