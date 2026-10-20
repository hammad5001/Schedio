@props([
    'size' => 'md', // sm (28px), md (38px), lg (48px), xl (64px)
    'showText' => true,
    'showSubtitle' => true,
    'subtitle' => 'Executive Workspace',
    'badge' => null,
    'animate' => false,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'w-7 h-7',
        'md' => 'w-10 h-10',
        'lg' => 'w-12 h-12',
        'xl' => 'w-16 h-16',
        default => 'w-10 h-10',
    };

    $textClasses = match($size) {
        'sm' => 'text-sm',
        'md' => 'text-[19px]',
        'lg' => 'text-2xl',
        'xl' => 'text-3xl',
        default => 'text-[19px]',
    };

    $svgId = 'schedio-delta-' . uniqid();
@endphp

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 select-none group']) }}>
    <!-- Schedio Delta-Chrono Luxury Emblem (Inspired by Reference) -->
    <div class="relative shrink-0 {{ $sizeClasses }} rounded-2xl transition-all duration-300 group-hover:scale-105 group-hover:shadow-[0_0_26px_rgba(245,158,11,0.35)] shadow-lg">
        <svg class="w-full h-full" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <!-- Deep Obsidian Core -->
                <linearGradient id="{{ $svgId }}-bg" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#1B202B"/>
                    <stop offset="50%" stop-color="#0E121A"/>
                    <stop offset="100%" stop-color="#07090E"/>
                </linearGradient>

                <!-- Radiant Golden Titanium Outline -->
                <linearGradient id="{{ $svgId }}-stroke" x1="10" y1="12" x2="38" y2="38" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#FFFDF5"/>
                    <stop offset="25%" stop-color="#FDE68A"/>
                    <stop offset="65%" stop-color="#F59E0B"/>
                    <stop offset="100%" stop-color="#D97706"/>
                </linearGradient>

                <!-- Specular Core Node -->
                <linearGradient id="{{ $svgId }}-node" x1="20" y1="20" x2="28" y2="28" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#FFFFFF"/>
                    <stop offset="50%" stop-color="#FDE68A"/>
                    <stop offset="100%" stop-color="#F59E0B"/>
                </linearGradient>

                <!-- Specular Container Border -->
                <linearGradient id="{{ $svgId }}-border" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                    <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.25"/>
                    <stop offset="40%" stop-color="#F59E0B" stop-opacity="0.5"/>
                    <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0.06"/>
                </linearGradient>
            </defs>

            <!-- Base Squircle Armor -->
            <rect x="1.5" y="1.5" width="45" height="45" rx="13" fill="url(#{{ $svgId }}-bg)"/>
            <rect x="1.5" y="1.5" width="45" height="45" rx="13" stroke="url(#{{ $svgId }}-border)" stroke-width="1.2"/>

            <!-- Delta-Chrono Geometric Shield -->
            <path d="M 12.5 14 H 35.5 C 37.2 14 38.2 15.9 37.3 17.3 L 25.8 35.8 C 24.9 37.2 23.1 37.2 22.2 35.8 L 10.7 17.3 C 9.8 15.9 10.8 14 12.5 14 Z"
                  stroke="url(#{{ $svgId }}-stroke)"
                  stroke-width="3.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"/>

            <!-- Internal S-Chrono Connector Path (Connecting top to center node, then down) -->
            <path d="M 16 18.5 L 24 24.5 L 20 31"
                  stroke="url(#{{ $svgId }}-stroke)"
                  stroke-width="3"
                  stroke-linecap="round"
                  stroke-linejoin="round"/>

            <!-- Precision Chrono Focal Node (The Onboarding / Time Center) -->
            <circle cx="24" cy="24.5" r="3.4" fill="url(#{{ $svgId }}-node)"/>
            <circle cx="24" cy="24.5" r="1.3" fill="#0E121A"/>

            <!-- Live Status Pulse Beacon (Top-Right) -->
            <circle cx="36" cy="12" r="2.2" fill="#34D399"/>
            <circle cx="36" cy="12" r="1" fill="#FFFFFF"/>
        </svg>
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
