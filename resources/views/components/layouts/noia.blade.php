@props(['title' => 'NoiaChat', 'header' => 'Panel'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-900">
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_1fr]">
        <aside class="bg-slate-900 px-6 py-8 text-slate-100">
            <div class="mb-8">
                <h1 class="text-2xl font-semibold">NoiaChat</h1>
                <p class="text-sm text-slate-400">Control y trazabilidad WhatsApp</p>
            </div>
            <nav class="space-y-2 text-sm">
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('dashboard') }}">Tablero</a>
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('contacts.index') }}">Contactos</a>
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('messages.index') }}">Mensajes</a>
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('conversations.index') }}">Conversaciones</a>
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('audit-logs.index') }}">Auditoría</a>
                <a class="block rounded px-3 py-2 hover:bg-slate-800" href="{{ route('settings.index') }}">Configuración</a>
            </nav>
        </aside>
        <main>
            <header class="border-b border-slate-200 bg-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">{{ $header }}</h2>
                    <div class="flex items-center gap-3 text-sm">
                        <span>{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded bg-slate-900 px-3 py-2 text-white">Salir</button>
                        </form>
                    </div>
                </div>
            </header>
            <section class="p-6">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
                @endif
                @if (session('error'))
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        <ul class="list-inside list-disc space-y-1">
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
