@props([
    'variant' => 'primary', // primary, secondary, danger, success, outline, ghost
    'size' => 'md',        // sm, md, lg
    'type' => 'button',
])

@php
    $variants = [
        'primary'   => 'bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white shadow-lg shadow-indigo-600/25 focus:ring-indigo-500',
        'secondary' => 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 focus:ring-slate-500',
        'success'   => 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-600/20 focus:ring-emerald-500',
        'danger'    => 'bg-rose-600 hover:bg-rose-500 text-white shadow-lg shadow-rose-600/20 focus:ring-rose-500',
        'outline'   => 'border border-slate-700 hover:border-slate-600 text-slate-300 hover:text-white bg-transparent focus:ring-indigo-500',
        'ghost'     => 'text-slate-400 hover:text-white hover:bg-slate-800/60 bg-transparent focus:ring-slate-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs rounded-lg',
        'md' => 'px-4 py-2 text-xs sm:text-sm rounded-xl',
        'lg' => 'px-5 py-2.5 text-sm sm:text-base rounded-xl',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 font-semibold transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
