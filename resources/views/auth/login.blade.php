@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="min-h-screen lg:h-screen lg:overflow-hidden flex flex-col justify-center items-center px-4 py-6 sm:px-6 relative bg-[#090C10] selection:bg-amber-400 selection:text-slate-950 overflow-hidden"
    x-data="{
        showPassword: false,
        realtimeClock: '',
        init() {
            const updateTime = () => {
                const now = new Date();
                let hours = now.getHours();
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12;
                this.realtimeClock = `${hours}:${minutes} ${ampm}`;
            };
            updateTime();
            setInterval(updateTime, 1000);
        }
    }">

    <!-- Atmospheric Dynamic Ambient Glows (Slow Breathing Aurora) -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
        <!-- Golden Amber Ambient Spotlight (Top-Left) -->
        <div class="absolute -top-32 -left-20 w-[600px] h-[450px] bg-gradient-to-br from-amber-500/15 via-amber-600/5 to-transparent rounded-full blur-[100px] animate-aurora"></div>

        <!-- Emerald/Teal Ambient Accent (Bottom-Right) -->
        <div class="absolute -bottom-32 -right-20 w-[550px] h-[450px] bg-gradient-to-tl from-emerald-500/10 via-teal-600/5 to-transparent rounded-full blur-[110px] animate-aurora" style="animation-delay: -5s;"></div>

        <!-- Precision Architectural Micro-Grid -->
        <div class="absolute inset-0 bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_65%_65%_at_50%_48%,#000_70%,transparent_100%)]"></div>
    </div>

    <!-- Floating Live Context Micro-Cards (High-End SaaS Floating Accents) -->
    <!-- Floating Card 1: Top Left with REAL-TIME Live Clock -->
    <div class="hidden xl:flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-[#141822]/85 border border-white/10 backdrop-blur-xl shadow-2xl absolute top-[22%] left-[10%] 2xl:left-[16%] animate-float-slow pointer-events-none select-none">
        <span class="relative flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
        </span>
        <div class="text-[11px] font-medium text-slate-200">
            <span class="font-bold text-white font-mono tracking-tight" x-text="realtimeClock || '{{ now()->format('h:i A') }}'">{{ now()->format('h:i A') }}</span> &bull; Client Onboarding Booked
        </div>
        <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">CONFIRMED</span>
    </div>

    <!-- Floating Card 2: Bottom Right -->
    <div class="hidden xl:flex items-center gap-2.5 px-3.5 py-2 rounded-xl bg-[#141822]/85 border border-white/10 backdrop-blur-xl shadow-2xl absolute bottom-[22%] right-[10%] 2xl:right-[16%] animate-float-reverse pointer-events-none select-none">
        <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <div class="text-[11px] font-medium text-slate-200">
            <span class="font-bold text-white">Conflict Engine</span> &bull; 0 Overlaps Detected
        </div>
        <span class="text-[9px] font-mono font-bold px-1.5 py-0.5 rounded bg-amber-500/15 text-amber-300 border border-amber-500/30">ACTIVE</span>
    </div>

    <!-- Main Form Container -->
    <div class="w-full max-w-[410px] mx-auto flex flex-col items-center relative z-10 transition-all duration-500">

        <!-- VIP Brand Header -->
        <div class="text-center mb-5 space-y-2">
            <div class="inline-flex items-center justify-center transition-transform duration-300 hover:scale-105">
                <x-brand-logo size="xl" :showText="false" />
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center justify-center gap-1.5">
                    Sign in to <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 font-extrabold">Schedio</span>
                </h1>
                <p class="text-xs text-slate-400 font-medium mt-1">
                    Executive Scheduling & Intelligent Meeting Suite
                </p>
            </div>
        </div>

        <!-- Glass Panel Login Card with Shimmer Beam -->
        <div class="w-full rounded-2xl bg-[#12161F]/90 border border-white/[0.09] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.85)] backdrop-blur-2xl p-6 sm:p-7 relative overflow-hidden group">

            <!-- Animated Shimmer Light Beam Across Top Edge -->
            <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400/90 to-transparent overflow-hidden">
                <div class="w-1/2 h-full bg-gradient-to-r from-transparent via-white to-transparent animate-shimmer-beam"></div>
            </div>

            @if (session('status'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-950/70 border border-emerald-500/30 text-emerald-300 text-xs font-medium flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-500/30 text-rose-300 text-xs font-medium space-y-1">
                    @foreach ($errors->all() as $error)
                        <div class="flex items-start gap-2">
                            <svg class="w-3.5 h-3.5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <form id="loginForm" class="space-y-4" action="{{ route('login.post') }}" method="POST">
                @csrf

                <!-- Work Email Input -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300">
                        Work Email
                    </label>
                    <div class="relative group/input">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within/input:text-amber-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            value="{{ old('email') }}"
                            placeholder="name@company.com"
                            class="w-full pl-9 pr-3.5 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 focus:bg-[#0c1017] transition-all text-xs font-medium">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300">
                            Password
                        </label>
                    </div>
                    <div class="relative group/input">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 group-focus-within/input:text-amber-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
                            placeholder="Enter your password"
                            class="w-full pl-9 pr-10 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 focus:bg-[#0c1017] transition-all text-xs font-medium">
                        <button type="button" @click="showPassword = !showPassword"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-amber-300 transition-colors cursor-pointer">
                            <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Device -->
                <div class="flex items-center justify-between pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input id="remember" name="remember" type="checkbox" checked
                            class="h-3.5 w-3.5 bg-[#090C11] border-white/20 rounded text-amber-500 focus:ring-amber-400 focus:ring-offset-0 cursor-pointer">
                        <span class="text-xs text-slate-400 font-medium hover:text-slate-300">Keep me signed in</span>
                    </label>
                </div>

                <!-- Sign In Action Button with Hover Light Sweep -->
                <div class="pt-1.5">
                    <button type="submit"
                        class="w-full relative overflow-hidden py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 shadow-[0_4px_16px_rgba(245,158,11,0.3)] hover:shadow-[0_6px_25px_rgba(245,158,11,0.5)] hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer group/btn">
                        <span>Sign In to Workspace</span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 group-hover/btn:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Bottom Switch Link -->
            <div class="mt-5 pt-4 border-t border-white/[0.08] text-center">
                <p class="text-xs text-slate-400">
                    Need an account?
                    <a href="{{ route('register') }}" class="font-bold text-amber-400 hover:text-amber-300 transition-colors ml-1 inline-flex items-center gap-0.5">
                        <span>Request Access</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </p>
            </div>
        </div>

        <!-- Trust & Security Minimalist Badges -->
        <div class="mt-4 flex items-center justify-center gap-3 text-[10px] text-slate-500 font-medium">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-amber-500/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span>256-Bit Encrypted</span>
            </span>
            <span class="w-1 h-1 rounded-full bg-slate-800"></span>
            <span>Multi-Tenant Scoped</span>
            <span class="w-1 h-1 rounded-full bg-slate-800"></span>
            <span>SOC-2 Certified</span>
        </div>
    </div>
</div>
@endsection
