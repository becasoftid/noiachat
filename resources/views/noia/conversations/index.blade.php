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
        $activeFilterCount = collect(['search', 'status', 'assigned_user_id', 'branch_id', 'date_from', 'date_to', 'quick'])
            ->filter(fn ($key) => request()->filled($key))
            ->count() + (request()->boolean('mine') ? 1 : 0);
        $baseQuery = request()->except(['page', 'mine', 'quick', 'assigned_user_id']);
    @endphp

    <div
        class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-[0_18px_50px_rgba(15,23,42,0.08)] lg:grid lg:h-[calc(100vh-190px)] lg:min-h-[660px]"
        :class="{ 'xl:grid-cols-[390px_minmax(0,1fr)_320px]': true }"
        x-data="{ filtersOpen: false, detailsOpen: false }"
    >
        <aside class="flex min-h-[660px] flex-col border-slate-200 bg-white lg:border-r">
            <div class="border-b border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-semibold text-slate-950">Inbox</h2>
                        <span class="inline-flex h-6 min-w-6 items-center justify-center rounded-full bg-emerald-600 px-2 text-xs font-bold text-white">
                            {{ $unreadConversationCount }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-cyan-200 hover:text-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                        title="Activar sonido de nuevos mensajes"
                        x-data="{ enabled: window.App.conversationSound.isEnabled() }"
                        x-on:click="enabled = window.App.conversationSound.toggle()"
                        x-bind:title="enabled ? 'Desactivar sonido de nuevos mensajes' : 'Activar sonido de nuevos mensajes'"
                        x-bind:aria-label="enabled ? 'Desactivar sonido de nuevos mensajes' : 'Activar sonido de nuevos mensajes'"
                        x-bind:class="enabled ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ''"
                    >
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M10 21h4" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>

                <form method="GET" class="mt-4 flex gap-2">
                    @if(request()->filled('conversation'))
                        <input type="hidden" name="conversation" value="{{ request('conversation') }}">
                    @endif
                    <label class="sr-only" for="conversation-search">Buscar conversación</label>
                    <input id="conversation-search" class="noia-input bg-white" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o teléfono">
                    <button class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-cyan-200 hover:text-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-100" aria-label="Buscar">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" stroke-linecap="round" />
                        </svg>
                    </button>
                </form>

                <div class="mt-3 flex flex-wrap gap-2 text-sm font-semibold">
                    <a href="{{ route('conversations.index', $baseQuery) }}" class="@class(['rounded-lg border px-3 py-2 transition focus:outline-none focus:ring-4 focus:ring-emerald-100', 'border-emerald-200 bg-emerald-50 text-emerald-700' => ! request()->boolean('mine') && ! request('quick'), 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => request()->boolean('mine') || request('quick')])">Todos</a>
                    <a href="{{ route('conversations.index', array_merge($baseQuery, ['mine' => 1])) }}" class="@class(['rounded-lg border px-3 py-2 transition focus:outline-none focus:ring-4 focus:ring-emerald-100', 'border-emerald-200 bg-emerald-50 text-emerald-700' => request()->boolean('mine'), 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => ! request()->boolean('mine')])">Míos</a>
                    <a href="{{ route('conversations.index', array_merge($baseQuery, ['quick' => 'unassigned'])) }}" class="@class(['rounded-lg border px-3 py-2 transition focus:outline-none focus:ring-4 focus:ring-emerald-100', 'border-emerald-200 bg-emerald-50 text-emerald-700' => request('quick') === 'unassigned', 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => request('quick') !== 'unassigned'])">No asignados</a>
                    <a href="{{ route('conversations.index', array_merge($baseQuery, ['quick' => 'unread'])) }}" class="@class(['rounded-lg border px-3 py-2 transition focus:outline-none focus:ring-4 focus:ring-emerald-100', 'border-emerald-200 bg-emerald-50 text-emerald-700' => request('quick') === 'unread', 'border-slate-200 bg-white text-slate-600 hover:border-slate-300' => request('quick') !== 'unread'])">No leídos</a>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2">
                    @can('messages.send')
                        <a href="{{ route('conversations.index', array_merge(request()->query(), ['new' => 1])) }}" class="noia-btn-success h-11 px-3">
                            <span class="text-lg leading-none">+</span>
                            <span class="hidden sm:inline">Nuevo chat</span>
                        </a>
                    @endcan
                    <button type="button" class="noia-btn-secondary h-11 px-3" x-on:click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen.toString()">
                        Filtros
                        @if($activeFilterCount > 0)
                            <span class="ml-2 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-cyan-700 px-1.5 text-xs font-bold text-white">{{ $activeFilterCount }}</span>
                        @endif
                    </button>
                    <a href="{{ route('reports.exports.conversations', request()->except('conversation')) }}" class="noia-btn-secondary h-11 px-3">Exportar</a>
                </div>

                @can('messages.send')
                    @if(request()->boolean('new'))
                        <form method="POST" action="{{ route('conversations.start') }}" class="mt-3 grid gap-2 rounded-lg border border-emerald-100 bg-emerald-50/70 p-3">
                            @csrf
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Iniciar conversación</p>
                            <label class="sr-only" for="new-contact">Contacto</label>
                            <select id="new-contact" class="noia-select bg-white" name="contact_id" required>
                                <option value="">Selecciona contacto</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}" @selected((old('contact_id') ?? request('contact_id')) === $contact->id)>
                                        {{ $contact->full_name }} · {{ $contact->primary_phone }}
                                    </option>
                                @endforeach
                            </select>
                            <label class="sr-only" for="new-channel">Canal</label>
                            <select id="new-channel" class="noia-select bg-white" name="channel_id" required>
                                <option value="">Selecciona canal</option>
                                @foreach($channels as $channel)
                                    <option value="{{ $channel->id }}" @selected((string) old('channel_id') === (string) $channel->id)>
                                        {{ $channel->name }}
                                    </option>
                                @endforeach
                            </select>
                            <button class="noia-btn-success">Abrir chat</button>
                        </form>
                    @endif
                @endcan

                <form method="GET" class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3" x-cloak x-show="filtersOpen" x-transition.opacity>
                    @if(request()->filled('conversation'))
                        <input type="hidden" name="conversation" value="{{ request('conversation') }}">
                    @endif
                    @if(($branches ?? collect())->isNotEmpty())
                        <select class="noia-select bg-white" name="branch_id">
                            <option value="">Todas las sedes</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @endif
                    <div class="grid gap-2 sm:grid-cols-2">
                        <select class="noia-select bg-white" name="status">
                            <option value="">Estado</option>
                            @foreach($statusLabels as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <select class="noia-select bg-white" name="assigned_user_id">
                            <option value="">Responsable</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) request('assigned_user_id') === (string) $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <input class="noia-input bg-white" type="date" name="date_from" value="{{ request('date_from') }}" aria-label="Desde">
                        <input class="noia-input bg-white" type="date" name="date_to" value="{{ request('date_to') }}" aria-label="Hasta">
                    </div>
                    <div class="grid gap-2 sm:grid-cols-[1fr_auto]">
                        <label class="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700">
                            <input class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-100" type="checkbox" name="mine" value="1" @checked(request()->boolean('mine'))>
                            <span>Mis conversaciones</span>
                        </label>
                        <button class="noia-btn-primary px-6">Filtrar</button>
                    </div>
                    @if($activeFilterCount > 0)
                        <a href="{{ route('conversations.index', request()->filled('conversation') ? ['conversation' => request('conversation')] : []) }}" class="text-sm font-semibold text-slate-500 transition hover:text-slate-900">Limpiar filtros</a>
                    @endif
                </form>
            </div>

            <div
                class="min-h-0 flex-1 overflow-y-auto bg-slate-50/60"
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
            <section class="hidden min-h-0 bg-[#f5fafb] lg:flex lg:items-center lg:justify-center xl:col-span-2">
                <div class="max-w-sm text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-white text-2xl font-bold text-[#10202a] shadow-sm">N</div>
                    <h3 class="text-lg font-semibold text-slate-950">Sin conversación seleccionada</h3>
                    <p class="mt-2 text-sm text-slate-500">Elige un chat de la lista para atenderlo o inicia uno nuevo desde contactos.</p>
                </div>
            </section>
        @endif
    </div>
</x-layouts.noia>
