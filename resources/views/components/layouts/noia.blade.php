@props(['title' => 'NoiaChat', 'header' => 'Panel'])

@php
    $navItems = [
        ['label' => 'Tablero', 'route' => 'dashboard', 'active' => 'dashboard'],
        ['label' => 'Contactos', 'route' => 'contacts.index', 'active' => 'contacts.*'],
        ['label' => 'Mensajes', 'route' => 'messages.index', 'active' => 'messages.*'],
        ['label' => 'Conversaciones', 'route' => 'conversations.index', 'active' => 'conversations.*'],
        ['label' => 'Auditoria', 'route' => 'audit-logs.index', 'active' => 'audit-logs.*'],
        ['label' => 'Usuarios', 'route' => 'users.index', 'active' => 'users.*', 'can' => 'admin.access'],
        ['label' => 'Configuracion', 'route' => 'settings.index', 'active' => 'settings.*', 'can' => 'admin.access'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('app.name', 'NoiaChat') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#eef4f7] text-slate-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="bg-[#10202a] px-5 py-5 text-white lg:min-h-screen lg:px-6 lg:py-8">
            <div class="flex items-center justify-between gap-4 lg:block">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-white text-[#10202a] shadow-xl shadow-cyan-950/30">
                        <span class="text-lg font-bold">N</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold leading-none">NoiaChat</h1>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.22em] text-cyan-100/70">Operations Hub</p>
                    </div>
                </a>

                <form method="POST" action="{{ route('logout') }}" class="lg:hidden">
                    @csrf
                    <button class="rounded-lg border border-white/10 px-3 py-2 text-sm font-semibold text-cyan-50 transition hover:bg-white/10">Salir</button>
                </form>
            </div>

            <div class="mt-8 hidden rounded-lg border border-white/10 bg-white/[0.08] p-4 text-sm text-cyan-50/80 lg:block">
                <div class="flex items-center justify-between gap-3">
                    <span>Canal WhatsApp</span>
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                </div>
                <p class="mt-3 text-xs leading-5 text-cyan-50/60">Control operativo, consentimientos y trazabilidad centralizada.</p>
            </div>

            <nav class="mt-6 grid gap-1 text-sm font-medium lg:mt-8">
                @foreach($navItems as $item)
                    @continue(isset($item['can']) && ! auth()->user()->can($item['can']))
                    <a
                        href="{{ route($item['route']) }}"
                        class="rounded-lg px-3 py-2.5 transition {{ request()->routeIs($item['active']) ? 'bg-white text-[#10202a] shadow-lg shadow-cyan-950/20' : 'text-cyan-50/75 hover:bg-white/10 hover:text-white' }}"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <main class="min-w-0">
            <header class="border-b border-slate-200 bg-white/90 px-5 py-5 backdrop-blur lg:px-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">NoiaChat</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ $header }}</h2>
                    </div>

                    <div class="hidden items-center gap-3 lg:flex">
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="noia-btn-secondary">Salir</button>
                        </form>
                    </div>
                </div>
            </header>

            <section class="px-5 py-6 lg:px-8 lg:py-8">
                @if (session('status'))
                    <div class="noia-alert-success">{{ session('status') }}</div>
                @endif

                @if (session('error'))
                    <div class="noia-alert-danger">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="noia-alert-danger">
                        <p class="font-semibold">Revisa los campos marcados</p>
                        <ul class="mt-2 list-inside list-disc space-y-1 font-normal">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </section>
        </main>
    </div>
</body>
</html>
