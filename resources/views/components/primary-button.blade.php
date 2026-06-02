<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex h-11 items-center justify-center rounded-lg border border-transparent bg-[#10202a] px-5 text-sm font-semibold text-white shadow-lg shadow-slate-900/10 transition hover:-translate-y-0.5 hover:bg-[#173141] focus:outline-none focus:ring-4 focus:ring-cyan-100']) }}>
    {{ $slot }}
</button>
