<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-[#eef4f7] px-5 py-8">
            <div class="mb-8">
                <a href="/" class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#10202a] text-white shadow-lg shadow-slate-900/20">
                        <span class="text-lg font-bold">N</span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold leading-none">NoiaChat</p>
                        <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-slate-500">Operations Hub</p>
                    </div>
                </a>
            </div>

            <div class="w-full max-w-md rounded-lg border border-slate-200 bg-white px-6 py-6 shadow-[0_24px_80px_rgba(15,23,42,0.12)] sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
