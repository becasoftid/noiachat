@props(['title' => 'Conversaciones'])

@php
    $navItems = [
        ['label' => 'Tablero', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'dashboard'],
        ['label' => 'Contactos', 'route' => 'contacts.index', 'active' => 'contacts.*', 'icon' => 'contacts'],
        ['label' => 'Mensajes', 'route' => 'messages.index', 'active' => 'messages.*', 'icon' => 'messages'],
        ['label' => 'Conversaciones', 'route' => 'conversations.index', 'active' => 'conversations.*', 'icon' => 'conversations'],
        ['label' => 'Fallos', 'route' => 'failures.index', 'active' => 'failures.*', 'icon' => 'failures', 'can' => 'platform.access'],
        ['label' => 'Salud', 'route' => 'health.index', 'active' => 'health.*', 'icon' => 'health', 'can' => 'platform.access'],
        ['label' => 'Auditoria', 'route' => 'audit-logs.index', 'active' => 'audit-logs.*', 'icon' => 'audit', 'can' => 'audit.view'],
        ['label' => 'Empresa', 'route' => 'tenancy.index', 'active' => 'tenancy.*', 'icon' => 'tenancy', 'can' => 'admin.access'],
        ['label' => 'WhatsApp', 'route' => 'whatsapp.channels.index', 'active' => 'whatsapp.channels.*', 'icon' => 'whatsapp', 'can' => 'whatsapp.integration.manage', 'feature' => 'settings.whatsapp_channel'],
        ['label' => 'Plan', 'route' => 'billing.index', 'active' => 'billing.*', 'icon' => 'billing', 'can' => 'admin.access'],
        ['label' => 'Usuarios', 'route' => 'users.index', 'active' => 'users.*', 'icon' => 'users', 'can' => 'admin.access'],
        ['label' => 'Configuracion', 'route' => 'settings.index', 'active' => 'settings.*', 'icon' => 'settings', 'can' => 'platform.access', 'feature' => 'settings.whatsapp_channel'],
    ];
    $tenantContext = $tenantContext ?? app(\App\Modules\Tenancy\Application\Services\TenantContext::class);
    $tenantMemberships = $tenantContext->memberships();
    $currentTenantMembership = $tenantContext->membership();
    $subscriptionFeatures = app(\App\Modules\Billing\Application\Services\SubscriptionFeatureService::class);
    $subscriptionNotice = app(\App\Modules\Billing\Application\Services\SubscriptionLifecycleService::class)->notice($tenantContext->companyId());
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name', 'NoiaChat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-dvh overflow-hidden bg-[#eef4f7] text-slate-900 antialiased">
    <div class="flex h-dvh min-h-0 flex-col">
        <header class="shrink-0 border-b border-slate-200 bg-white/95 px-3 py-2 shadow-sm backdrop-blur lg:px-4">
            <div class="flex min-h-14 items-center gap-3">
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-3 rounded-lg pr-2 focus:outline-none focus:ring-4 focus:ring-cyan-100">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#10202a] text-sm font-bold text-white shadow-sm">N</span>
                    <span class="hidden leading-tight sm:block">
                        <span class="block text-sm font-bold text-slate-950">NoiaChat</span>
                        <span class="block text-[10px] font-semibold uppercase tracking-[0.22em] text-cyan-700">Consola</span>
                    </span>
                </a>

                <div class="hidden min-w-[150px] border-l border-slate-200 pl-3 leading-tight lg:block">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700">Vista activa</p>
                    <h1 class="truncate text-sm font-bold text-slate-950">{{ $title }}</h1>
                </div>

                <nav class="flex min-w-0 flex-1 gap-1 overflow-x-auto text-sm font-semibold">
                    @foreach($navItems as $item)
                        @continue(isset($item['can']) && ! auth()->user()->can($item['can']))
                        @continue(isset($item['feature']) && ! auth()->user()->hasRole('super_admin') && ! $subscriptionFeatures->allows($tenantContext->companyId(), $item['feature']))
                        <a
                            href="{{ route($item['route']) }}"
                            title="{{ $item['label'] }}"
                            aria-label="{{ $item['label'] }}"
                            class="group relative inline-flex h-10 shrink-0 items-center gap-2 rounded-lg px-3 transition focus:outline-none focus:ring-4 focus:ring-cyan-100 {{ request()->routeIs($item['active']) ? 'bg-[#10202a] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' }}"
                        >
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center">
                                @switch($item['icon'])
                                    @case('dashboard')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 4h7v7H4zM13 4h7v5h-7zM13 11h7v9h-7zM4 13h7v7H4z" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('contacts')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" stroke-linecap="round" />
                                            <circle cx="9.5" cy="7" r="4" />
                                            <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" />
                                        </svg>
                                        @break
                                    @case('messages')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('conversations')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M7 8h10M7 12h6" stroke-linecap="round" />
                                            <path d="M4 18a8 8 0 1 1 3 2.24L3 21z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('audit')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M9 11l2 2 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M20 6l-8-3-8 3v6c0 5 3.4 8.7 8 9 4.6-.3 8-4 8-9z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('failures')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 9v4M12 17h.01" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10.3 4.4 2.5 18a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 4.4a2 2 0 0 0-3.4 0z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('health')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M22 12h-4l-3 8-6-16-3 8H2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('users')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke-linecap="round" />
                                            <circle cx="9" cy="7" r="4" />
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" />
                                        </svg>
                                        @break
                                    @case('tenancy')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 21h18M5 21V7l7-4 7 4v14" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M9 21v-6h6v6M9 9h.01M15 9h.01M9 12h.01M15 12h.01" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('billing')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M4 7h16M4 12h16M7 17h10" stroke-linecap="round" />
                                            <rect x="3" y="4" width="18" height="16" rx="2" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('whatsapp')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M7 8h10M7 12h5" stroke-linecap="round" />
                                            <path d="M4 18a8 8 0 1 1 3 2.24L3 21z" stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M16 14l2 2 3-4" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                    @case('settings')
                                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="3" />
                                            <path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.42 1.1V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 8.6 19.4a1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1.1-.42H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.6 8.6a1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .42-1.1V3a2 2 0 1 1 4 0v.09A1.7 1.7 0 0 0 15.4 4.6a1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.2.34.2.74.6 1 .32.22.7.33 1.1.33H21a2 2 0 1 1 0 4h-.09A1.7 1.7 0 0 0 19.4 15z" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        @break
                                @endswitch
                            </span>
                            <span class="hidden 2xl:inline">{{ $item['label'] }}</span>
                            <span class="pointer-events-none absolute left-1/2 top-full z-30 mt-2 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-950 px-2.5 py-1.5 text-xs font-semibold text-white opacity-0 shadow-lg transition group-hover:opacity-100 group-focus-visible:opacity-100 lg:block 2xl:hidden">
                                {{ $item['label'] }}
                            </span>
                        </a>
                    @endforeach
                </nav>

                <div class="hidden shrink-0 items-center gap-3 xl:flex">
                    @if($currentTenantMembership)
                        @if($tenantMemberships->count() > 1)
                            <form method="POST" action="{{ route('tenant-context.update') }}">
                                @csrf
                                <label class="sr-only" for="tenant-membership">Empresa y sede activa</label>
                                <select
                                    id="tenant-membership"
                                    name="membership_id"
                                    class="h-10 max-w-[280px] rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm transition focus:border-cyan-300 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                    onchange="this.form.submit()"
                                >
                                    @foreach($tenantMemberships as $membership)
                                        <option value="{{ $membership->id }}" @selected($currentTenantMembership->id === $membership->id)>
                                            {{ $membership->company->name }}{{ $membership->branch ? ' / '.$membership->branch->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @else
                            <div class="max-w-[260px] rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-right">
                                <p class="truncate text-[10px] font-semibold uppercase tracking-[0.16em] text-cyan-700">Empresa activa</p>
                                <p class="truncate text-sm font-semibold text-slate-800">
                                    {{ $currentTenantMembership->company->name }}{{ $currentTenantMembership->branch ? ' / '.$currentTenantMembership->branch->name : '' }}
                                </p>
                            </div>
                        @endif
                    @endif

                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="noia-btn-secondary h-10 px-4">Salir</button>
                    </form>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="sr-only" x-data x-init="window.App.toast({ type: 'success', message: @js(session('status')) })"></div>
        @endif

        @if (session('error'))
            <div class="sr-only" x-data x-init="window.App.toast({ type: 'error', message: @js(session('error')) })"></div>
        @endif

        @if ($subscriptionNotice || $errors->any())
            <div class="shrink-0 px-3 py-2">
                @if ($subscriptionNotice)
                    <div class="{{ $subscriptionNotice['type'] === 'danger' ? 'noia-alert-danger' : 'noia-alert-warning' }} mb-0">
                        {{ $subscriptionNotice['message'] }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="noia-alert-danger mb-0">
                        <p class="font-semibold">Revisa los campos marcados</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 font-normal">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <main class="min-h-0 w-full max-w-full flex-1 overflow-hidden">
            {{ $slot }}
        </main>
    </div>
</body>
</html>
