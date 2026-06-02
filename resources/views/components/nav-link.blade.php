@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-cyan-600 px-1 pt-1 text-sm font-semibold leading-5 text-slate-950 transition focus:outline-none focus:border-cyan-700'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-semibold leading-5 text-slate-500 transition hover:border-slate-300 hover:text-slate-800 focus:outline-none focus:border-slate-300 focus:text-slate-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
