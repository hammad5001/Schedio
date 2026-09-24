@props([
    'title',
    'value',
    'subtitle' => null,
    'icon' => null,
    'color' => 'indigo',
    'trend' => null,
])

@php
    $colorClasses = [
        'indigo' => 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20',
        'purple' => 'text-purple-400 bg-purple-500/10 border-purple-500/20',
        'emerald' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        'amber' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
        'rose' => 'text-rose-400 bg-rose-500/10 border-rose-500/20',
        'cyan' => 'text-cyan-400 bg-cyan-500/10 border-cyan-500/20',
    ][$color] ?? 'text-indigo-400 bg-indigo-500/10 border-indigo-500/20';
@endphp

<div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 shadow-xl relative overflow-hidden group hover:border-slate-700 transition-all duration-300">
    <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $title }}</span>
        @if ($icon)
            <div class="p-2 rounded-xl border {{ $colorClasses }}">
                {!! $icon !!}
            </div>
        @endif
    </div>

    <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">{{ $value }}</span>
        @if ($trend)
            <span class="text-xs font-semibold {{ str_starts_with($trend, '+') ? 'text-emerald-400' : 'text-amber-400' }}">
                {{ $trend }}
            </span>
        @endif
    </div>

    @if ($subtitle)
        <p class="mt-1 text-xs text-slate-400 leading-normal">{{ $subtitle }}</p>
    @endif
</div>
