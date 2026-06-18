<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NoiaChat') }} | Verificacion</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#eef4f7] font-sans text-slate-900 antialiased">
        <main class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8">
            <section class="w-full max-w-[440px] rounded-lg border border-slate-200 bg-white p-6 shadow-[0_24px_80px_rgba(15,23,42,0.12)] sm:p-8">
                <div class="mb-8 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#10202a] text-white shadow-lg">
                        <span class="font-bold">N</span>
                    </div>
                    <div>
                        <p class="font-semibold leading-none">NoiaChat</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Operations Hub</p>
                    </div>
                </div>

                <div class="mb-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Verificacion requerida</p>
                    <h1 class="mt-3 text-3xl font-semibold text-slate-950">Confirma tu acceso</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        Enviamos un codigo de seis digitos al correo del administrador.
                    </p>
                </div>

                @if (session('auth.two_factor.plain_code'))
                    <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Codigo local: <span class="font-semibold">{{ session('auth.two_factor.plain_code') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="code" class="text-sm font-semibold text-slate-800">Codigo de verificacion</label>
                        <div class="mt-2">
                            <input
                                id="code"
                                class="block h-12 w-full rounded-lg border border-slate-200 bg-slate-50 px-4 text-center text-lg font-semibold tracking-[0.28em] text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-cyan-500 focus:bg-white focus:ring-4 focus:ring-cyan-100"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                maxlength="6"
                                name="code"
                                required
                                autofocus
                                autocomplete="one-time-code"
                                placeholder="000000"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>

                    <button type="submit" class="flex h-12 w-full items-center justify-center rounded-lg bg-[#10202a] px-5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-[#173141] focus:outline-none focus:ring-4 focus:ring-cyan-100">
                        Verificar acceso
                    </button>
                </form>
            </section>
        </main>
    </body>
</html>
