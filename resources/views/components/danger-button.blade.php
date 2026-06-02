<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-11 items-center justify-center rounded-lg border border-transparent bg-rose-600 px-5 text-sm font-semibold text-white shadow-lg shadow-rose-900/10 transition hover:-translate-y-0.5 hover:bg-rose-500 focus:outline-none focus:ring-4 focus:ring-rose-100']) }}>
    {{ $slot }}
</button>
