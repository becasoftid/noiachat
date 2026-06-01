<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NoiaChat') }} | Acceso</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#eef4f7] font-sans text-slate-900 antialiased">
        <main class="min-h-screen lg:grid lg:grid-cols-[minmax(0,1fr)_520px]">
            <section class="relative hidden overflow-hidden bg-[#10202a] px-12 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div class="absolute inset-0 opacity-80">
                    <div class="absolute left-[-120px] top-[-140px] h-[360px] w-[360px] rounded-full bg-cyan-500/20 blur-3xl"></div>
                    <div class="absolute bottom-[-160px] right-[-140px] h-[420px] w-[420px] rounded-full bg-emerald-400/15 blur-3xl"></div>
                </div>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-[#10202a] shadow-xl shadow-cyan-950/30">
                        <span class="text-lg font-bold">N</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold leading-none">NoiaChat</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.24em] text-cyan-100/75">Operations Hub</p>
                    </div>
                </div>

                <div class="relative z-10 max-w-2xl">
                    <div class="mb-8 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-sm font-medium text-cyan-50 backdrop-blur">
                        <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                        Plataforma de mensajeria activa
                    </div>

                    <h1 class="max-w-2xl text-5xl font-semibold leading-tight text-white xl:text-6xl">
                        Conversaciones, consentimiento y trazabilidad en un mismo panel.
                    </h1>

                    <div class="mt-10 grid max-w-xl grid-cols-3 gap-3">
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-2xl font-semibold">55</p>
                            <p class="mt-1 text-xs font-medium text-cyan-50/70">rutas operativas</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-2xl font-semibold">3</p>
                            <p class="mt-1 text-xs font-medium text-cyan-50/70">roles base</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-2xl font-semibold">24/7</p>
                            <p class="mt-1 text-xs font-medium text-cyan-50/70">flujo WhatsApp</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 grid max-w-2xl grid-cols-[1fr_auto] items-end gap-6">
                    <div class="rounded-lg border border-white/10 bg-white/[0.08] p-5 backdrop-blur">
                        <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-4">
                            <p class="text-sm font-semibold text-cyan-50">Estado del canal</p>
                            <span class="rounded-full bg-emerald-300/15 px-3 py-1 text-xs font-semibold text-emerald-100">Listo</span>
                        </div>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-cyan-50/70">Mensajes en cola</span>
                                <span class="font-semibold">12</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-cyan-50/70">Conversaciones abiertas</span>
                                <span class="font-semibold">8</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-cyan-50/70">Alertas de compliance</span>
                                <span class="font-semibold text-amber-100">2</span>
                            </div>
                        </div>
                    </div>
                    <div class="h-28 w-28 rounded-lg border border-white/10 bg-[linear-gradient(135deg,rgba(255,255,255,.22),rgba(255,255,255,.05))] p-3 backdrop-blur">
                        <div class="h-full rounded-md bg-[#c7f9df] p-2">
                            <div class="h-2 w-12 rounded-full bg-[#10202a]"></div>
                            <div class="mt-4 grid grid-cols-3 gap-1">
                                <span class="h-8 rounded bg-[#10202a]/80"></span>
                                <span class="h-8 rounded bg-emerald-600"></span>
                                <span class="h-8 rounded bg-cyan-700"></span>
                            </div>
                            <div class="mt-3 h-2 rounded-full bg-[#10202a]/35"></div>
                            <div class="mt-2 h-2 w-14 rounded-full bg-[#10202a]/25"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:bg-white">
                <div class="w-full max-w-[440px]">
                    <div class="mb-10 flex items-center justify-between gap-4 lg:hidden">
                        <a href="/" class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#10202a] text-white shadow-lg">
                                <span class="font-bold">N</span>
                            </div>
                            <div>
                                <p class="font-semibold leading-none">NoiaChat</p>
                                <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Operations Hub</p>
                            </div>
                        </a>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.12)] sm:p-8 lg:border-0 lg:p-0 lg:shadow-none">
                        <div class="mb-8">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Acceso seguro</p>
                            <h2 class="mt-3 text-3xl font-semibold text-slate-950">Ingresa a tu panel</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                Gestiona contactos, conversaciones y envios con trazabilidad operativa.
                            </p>
                        </div>

                        <x-auth-session-status class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" :status="session('status')" />

                        <form method="POST" action="{{ route('login') }}" class="space-y-5">
                            @csrf

                            <div>
                                <label for="email" class="text-sm font-semibold text-slate-800">Correo electronico</label>
                                <div class="mt-2">
                                    <input
                                        id="email"
                                        class="block h-12 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                        type="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        autofocus
                                        autocomplete="username"
                                        placeholder="admin@noiachat.local"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <div class="flex items-center justify-between gap-4">
                                    <label for="password" class="text-sm font-semibold text-slate-800">Contrasena</label>
                                    @if (Route::has('password.request'))
                                        <a class="text-sm font-semibold text-cyan-700 transition hover:text-cyan-900 focus:outline-none focus:ring-4 focus:ring-cyan-100" href="{{ route('password.request') }}">
                                            Recuperar acceso
                                        </a>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <input
                                        id="password"
                                        class="block h-12 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                        type="password"
                                        name="password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="Ingresa tu contrasena"
                                    />
                                </div>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <label for="remember_me" class="inline-flex items-center gap-3">
                                    <input id="remember_me" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-cyan-700 shadow-sm focus:ring-cyan-500" name="remember">
                                    <span class="text-sm font-medium text-slate-600">Mantener sesion activa</span>
                                </label>
                            </div>

                            <button type="submit" class="flex h-12 w-full items-center justify-center rounded-lg bg-[#10202a] px-5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-[#173141] focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                Iniciar sesion
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
