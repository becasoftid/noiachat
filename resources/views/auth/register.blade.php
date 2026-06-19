<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NoiaChat') }} | Registro</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[#eef4f7] font-sans text-slate-900 antialiased">
        <main class="min-h-screen lg:grid lg:grid-cols-[420px_minmax(0,1fr)]">
            <section class="relative hidden overflow-hidden bg-[#10202a] px-10 py-10 text-white lg:flex lg:flex-col lg:justify-between">
                <a href="/" class="relative z-10 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-[#10202a] shadow-xl shadow-cyan-950/30">
                        <span class="text-lg font-bold">N</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold leading-none">NoiaChat</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.24em] text-cyan-100/75">Operations Hub</p>
                    </div>
                </a>

                <div class="relative z-10">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-100/80">Prueba basica</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight text-white">
                        Crea tu empresa y empieza a operar desde una sede inicial.
                    </h1>
                    <p class="mt-5 text-sm leading-6 text-cyan-50/75">
                        El registro configura el usuario responsable, la empresa, la sede principal y el periodo de prueba.
                    </p>

                    <div class="mt-8 space-y-3">
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-sm font-semibold text-cyan-50">Rol inicial</p>
                            <p class="mt-1 text-sm text-cyan-50/70">Administrador de empresa durante el trial.</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-sm font-semibold text-cyan-50">Aislamiento</p>
                            <p class="mt-1 text-sm text-cyan-50/70">Contactos, conversaciones y reportes quedan dentro de tu empresa.</p>
                        </div>
                        <div class="rounded-lg border border-white/10 bg-white/[0.08] p-4 backdrop-blur">
                            <p class="text-sm font-semibold text-cyan-50">Plan</p>
                            <p class="mt-1 text-sm text-cyan-50/70">El sistema asigna el plan basico de prueba automaticamente.</p>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 rounded-lg border border-white/10 bg-white/[0.08] p-5 backdrop-blur">
                    <div class="flex items-center justify-between gap-4">
                        <p class="text-sm font-semibold text-cyan-50">Estado de alta</p>
                        <span class="rounded-full bg-emerald-300/15 px-3 py-1 text-xs font-semibold text-emerald-100">Listo</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-cyan-50/70">
                        Despues del registro entraras al panel con tu empresa activa.
                    </p>
                </div>
            </section>

            <section class="px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
                <div class="mx-auto flex min-h-full w-full max-w-5xl flex-col">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <a href="/" class="flex items-center gap-3 lg:hidden">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#10202a] text-white shadow-lg">
                                <span class="font-bold">N</span>
                            </div>
                            <div>
                                <p class="font-semibold leading-none">NoiaChat</p>
                                <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Operations Hub</p>
                            </div>
                        </a>

                        <a
                            href="{{ route('login') }}"
                            class="ml-auto rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-cyan-800 shadow-sm transition hover:border-cyan-200 hover:text-cyan-950 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                        >
                            Ya tienes cuenta?
                        </a>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white shadow-[0_24px_80px_rgba(15,23,42,0.12)]">
                        <div class="border-b border-slate-200 px-6 py-6 sm:px-8">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-cyan-700">Registro seguro</p>
                            <div class="mt-3 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                <div>
                                    <h2 class="text-3xl font-semibold text-slate-950">Empieza tu prueba</h2>
                                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                                        Completa los datos base para crear tu cuenta, registrar la empresa y dejar lista la sede principal.
                                    </p>
                                </div>
                                <div class="rounded-lg border border-cyan-100 bg-cyan-50 px-4 py-3 text-sm font-medium text-cyan-950">
                                    Plan basico de prueba incluido.
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('register') }}" class="px-6 py-6 sm:px-8">
                            @csrf

                            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                                <div class="space-y-8">
                                    <section>
                                        <div class="mb-4 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
                                            <div>
                                                <h3 class="text-base font-semibold text-slate-950">Responsable</h3>
                                                <p class="mt-1 text-sm text-slate-600">Datos de acceso del usuario inicial.</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Paso 1</span>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="name" :value="__('Nombre del responsable')" />
                                                <x-text-input id="name" class="mt-1 block h-12 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="email" :value="__('Correo de acceso')" />
                                                <x-text-input id="email" class="mt-1 block h-12 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                            </div>
                                        </div>
                                    </section>

                                    <section>
                                        <div class="mb-4 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
                                            <div>
                                                <h3 class="text-base font-semibold text-slate-950">Empresa</h3>
                                                <p class="mt-1 text-sm text-slate-600">Organizacion principal del periodo de prueba.</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Paso 2</span>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="company_name" :value="__('Nombre comercial')" />
                                                <x-text-input id="company_name" class="mt-1 block h-12 w-full" type="text" name="company_name" :value="old('company_name')" required autocomplete="organization" />
                                                <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="company_legal_name" :value="__('Razon social')" />
                                                <x-text-input id="company_legal_name" class="mt-1 block h-12 w-full" type="text" name="company_legal_name" :value="old('company_legal_name')" autocomplete="organization" />
                                                <x-input-error :messages="$errors->get('company_legal_name')" class="mt-2" />
                                            </div>

                                            <div class="md:col-span-2">
                                                <x-input-label for="company_tax_id" :value="__('NIT / identificacion tributaria')" />
                                                <x-text-input id="company_tax_id" class="mt-1 block h-12 w-full" type="text" name="company_tax_id" :value="old('company_tax_id')" />
                                                <x-input-error :messages="$errors->get('company_tax_id')" class="mt-2" />
                                            </div>
                                        </div>
                                    </section>

                                    <section>
                                        <div class="mb-4 flex items-center justify-between gap-4 border-b border-slate-200 pb-3">
                                            <div>
                                                <h3 class="text-base font-semibold text-slate-950">Sede inicial</h3>
                                                <p class="mt-1 text-sm text-slate-600">Primera sede para operar contactos y conversaciones.</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Paso 3</span>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <x-input-label for="branch_name" :value="__('Nombre de la sede')" />
                                                <x-text-input id="branch_name" class="mt-1 block h-12 w-full" type="text" name="branch_name" :value="old('branch_name', 'Principal')" required />
                                                <x-input-error :messages="$errors->get('branch_name')" class="mt-2" />
                                            </div>

                                            <div>
                                                <x-input-label for="branch_city" :value="__('Ciudad')" />
                                                <x-text-input id="branch_city" class="mt-1 block h-12 w-full" type="text" name="branch_city" :value="old('branch_city')" autocomplete="address-level2" />
                                                <x-input-error :messages="$errors->get('branch_city')" class="mt-2" />
                                            </div>
                                        </div>
                                    </section>
                                </div>

                                <aside class="space-y-5 xl:border-l xl:border-slate-200 xl:pl-8">
                                    <section>
                                        <div class="mb-4 border-b border-slate-200 pb-3">
                                            <h3 class="text-base font-semibold text-slate-950">Seguridad</h3>
                                            <p class="mt-1 text-sm text-slate-600">Define la contrasena de ingreso.</p>
                                        </div>

                                        <div class="space-y-4">
                                            <div x-data="{ showPassword: false }">
                                                <x-input-label for="password" :value="__('Contrasena')" />
                                                <div class="relative mt-1">
                                                    <x-text-input
                                                        id="password"
                                                        class="block h-12 w-full pr-12"
                                                        x-bind:type="showPassword ? 'text' : 'password'"
                                                        type="password"
                                                        name="password"
                                                        required
                                                        autocomplete="new-password"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-lg text-slate-500 transition hover:text-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                        x-on:click="showPassword = !showPassword"
                                                        x-bind:title="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                                                        x-bind:aria-label="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                                                    >
                                                        <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                        <svg x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                                            <path d="M10.7 5.2A9.7 9.7 0 0 1 12 5c6 0 9.5 7 9.5 7a16.9 16.9 0 0 1-3 3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M14.1 14.1A3 3 0 0 1 9.9 9.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M6.6 6.6C3.8 8.5 2.5 12 2.5 12s3.5 7 9.5 7a9.4 9.4 0 0 0 4.2-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                                            </div>

                                            <div x-data="{ showPassword: false }">
                                                <x-input-label for="password_confirmation" :value="__('Confirmar contrasena')" />
                                                <div class="relative mt-1">
                                                    <x-text-input
                                                        id="password_confirmation"
                                                        class="block h-12 w-full pr-12"
                                                        x-bind:type="showPassword ? 'text' : 'password'"
                                                        type="password"
                                                        name="password_confirmation"
                                                        required
                                                        autocomplete="new-password"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-lg text-slate-500 transition hover:text-cyan-800 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                                        x-on:click="showPassword = !showPassword"
                                                        x-bind:title="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                                                        x-bind:aria-label="showPassword ? 'Ocultar contrasena' : 'Mostrar contrasena'"
                                                    >
                                                        <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                        <svg x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                            <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                                            <path d="M10.7 5.2A9.7 9.7 0 0 1 12 5c6 0 9.5 7 9.5 7a16.9 16.9 0 0 1-3 3.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M14.1 14.1A3 3 0 0 1 9.9 9.9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                            <path d="M6.6 6.6C3.8 8.5 2.5 12 2.5 12s3.5 7 9.5 7a9.4 9.4 0 0 0 4.2-1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </button>
                                                </div>
                                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                                            </div>
                                        </div>
                                    </section>

                                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4">
                                        <p class="text-sm font-semibold text-slate-950">Que se crea?</p>
                                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                                            <li>Usuario administrador de empresa.</li>
                                            <li>Empresa y sede principal.</li>
                                            <li>Suscripcion trial del plan basico.</li>
                                        </ul>
                                    </div>

                                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center xl:flex-col-reverse xl:items-stretch">
                                        <a
                                            class="flex h-12 items-center justify-center rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-cyan-800 transition hover:border-cyan-200 hover:text-cyan-950 focus:outline-none focus:ring-4 focus:ring-cyan-100"
                                            href="{{ route('login') }}"
                                        >
                                            Ya tienes cuenta?
                                        </a>

                                        <button type="submit" class="flex h-12 items-center justify-center rounded-lg bg-[#10202a] px-5 text-sm font-semibold text-white shadow-lg shadow-slate-900/20 transition hover:-translate-y-0.5 hover:bg-[#173141] focus:outline-none focus:ring-4 focus:ring-cyan-100">
                                            Empezar prueba
                                        </button>
                                    </div>
                                </aside>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
