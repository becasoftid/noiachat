<x-layouts.noia title="Conversaciones" header="Conversaciones">
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
        $customerCareWindowClosed = $freeFormEligibility?->value === 'blocked_customer_care_window';
    @endphp
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.08)] lg:grid lg:h-[calc(100vh-190px)] lg:min-h-[650px] lg:grid-cols-[460px_minmax(0,1fr)]">
        <aside class="flex min-h-[650px] flex-col border-slate-200 bg-white lg:border-r">
            <div class="border-b border-slate-200 bg-slate-50/80 p-4">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan-700">Inbox</p>
                        <h2 class="text-lg font-semibold text-slate-950">Chats</h2>
                    </div>
                    <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">{{ $conversations->total() }}</span>
                </div>

                <form method="GET" class="grid gap-2">
                    <input class="noia-input bg-white" name="search" value="{{ request('search') }}" placeholder="Buscar contacto o teléfono">
                    <div class="grid gap-2 sm:grid-cols-2">
                        <select class="noia-select bg-white" name="status">
                            <option value="">Estado</option>
                            @foreach($statusLabels as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="noia-select bg-white" name="assigned_user_id">
                            <option value="">Asignado</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input class="noia-input bg-white" type="date" name="date_from" value="{{ request('date_from') }}">
                        <input class="noia-input bg-white" type="date" name="date_to" value="{{ request('date_to') }}">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                            <input class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100" type="checkbox" name="mine" value="1" @checked(request()->boolean('mine'))>
                            <span>Mis conversaciones</span>
                        </label>
                        <button class="noia-btn-primary px-6">Filtrar</button>
                    </div>
                </form>
            </div>

            <div
                class="min-h-0 flex-1 overflow-y-auto"
                x-data="App.conversationInbox($el.dataset.refreshUrl)"
                x-init="start()"
                x-on:beforeunload.window="stop()"
                data-refresh-url="{{ route('conversations.refresh', request()->query()) }}"
            >
                <div x-ref="list">
                    @include('noia.conversations.partials.list', [
                        'conversations' => $conversations,
                        'statusLabels' => $statusLabels,
                        'activeConversationId' => $conversation?->id,
                    ])
                </div>
            </div>
        </aside>

        @if($conversation)
            @include('noia.conversations.partials.panel')
        @else
            <section class="hidden bg-[#eef5f7] lg:flex lg:items-center lg:justify-center">
                <div class="max-w-sm text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white text-2xl font-bold text-[#10202a] shadow-sm">N</div>
                    <h3 class="text-lg font-semibold text-slate-950">Sin conversación seleccionada</h3>
                    <p class="mt-2 text-sm text-slate-500">Elige un chat de la lista para atenderlo.</p>
                </div>
            </section>
        @endif
    </div>
</x-layouts.noia>
