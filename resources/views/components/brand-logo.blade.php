@props([
    'size' => 'md', // sm (32px), md (44px), lg (56px), xl (80px)
    'showText' => true,
    'showSubtitle' => true,
    'subtitle' => 'Executive Workspace',
    'badge' => null,
    'animate' => false,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-8 h-8 rounded-xl p-1',
        'md' => 'w-11 h-11 rounded-2xl p-1.5',
        'lg' => 'w-14 h-14 rounded-2xl p-2',
        'xl' => 'w-20 h-20 sm:w-22 sm:h-22 rounded-3xl p-2.5',
        default => 'w-11 h-11 rounded-2xl p-1.5',
    };

    $textClasses = match($size) {
        'sm' => 'text-sm',
        'md' => 'text-[19px]',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-[19px]',
    };
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none group cursor-pointer']) }}>
    <!-- Schedio Luxury Golden Calendar Emblem -->
    <div class="relative shrink-0 {{ $sizeClasses }} flex items-center justify-center bg-gradient-to-br from-[#181C24] via-[#0E1117] to-[#07090D] shadow-2xl shadow-black/80 ring-1 ring-amber-400/40 transition-all duration-300 group-hover:scale-105 group-hover:ring-amber-300 group-hover:shadow-[0_0_35px_rgba(245,158,11,0.5)]">
        <img src="{{ asset('images/schedio-logo.png') }}"
             alt="Schedio"
             class="w-full h-full object-contain filter drop-shadow-[0_4px_12px_rgba(245,158,11,0.35)] select-none pointer-events-none" />
    </div>

    @if ($showText)
        <div class="flex flex-col justify-center">
            <!-- Brand Name + Badge -->
            <div class="flex items-center gap-2">
                <span class="{{ $textClasses }} font-black tracking-tight text-white group-hover:text-amber-100 transition-colors">
                    Schedio
                </span>

                @if ($badge)
                    <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded-md bg-amber-500/15 border border-amber-500/30 text-amber-300 uppercase tracking-wider">
                        {{ $badge }}
                    </span>
                @endif
            </div>

            <!-- Professional Subtitle with Live Pulse -->
            @if ($showSubtitle)
                <div class="inline-flex items-center gap-1.5 mt-0.5">
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-400"></span>
                    </span>
                    <span class="text-[10px] font-semibold tracking-wider uppercase text-slate-400 group-hover:text-slate-300 transition-colors font-mono">
                        {{ $subtitle }}
                    </span>
                </div>
            @endif
        </div>
    @endif
</div>
