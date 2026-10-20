@extends('layouts.app')

@section('title', 'Request Workspace Access')

@section('content')
<div class="min-h-screen flex flex-col justify-center items-center px-4 py-8 sm:px-6 relative bg-[#090C10] selection:bg-amber-400 selection:text-slate-950"
    x-data="{ regType: '{{ old('registration_type', 'company_super_admin') }}' }">

    <!-- Subtle Technical Grid & Ambient Glow -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden">
        <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[800px] h-[450px] bg-gradient-to-b from-amber-500/[0.12] via-amber-600/[0.04] to-transparent rounded-full blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px] [mask-image:radial-gradient(ellipse_60%_60%_at_50%_45%,#000_70%,transparent_100%)]"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-[540px] mx-auto flex flex-col items-center">

        <!-- VIP Brand Header -->
        <div class="text-center mb-5 space-y-2">
            <div class="inline-flex items-center justify-center">
                <x-brand-logo size="xl" :showText="false" />
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-white flex items-center justify-center gap-1.5">
                    Join <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-200 via-amber-400 to-amber-500 font-extrabold">Schedio</span> Enterprise
                </h1>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    Register a new company workspace or join an existing team
                </p>
            </div>
        </div>

        <!-- Glass Panel Register Card -->
        <div class="w-full rounded-2xl bg-[#12161F]/90 border border-white/[0.08] shadow-[0_20px_60px_-15px_rgba(0,0,0,0.8)] backdrop-blur-2xl p-6 sm:p-7 relative overflow-hidden">
            <!-- Top Accent Micro-line -->
            <div class="absolute top-0 left-0 right-0 h-[1.5px] bg-gradient-to-r from-transparent via-amber-400/80 to-transparent"></div>

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

            <!-- Segmented Control Mode Switcher -->
            <div class="mb-4 p-1 rounded-xl bg-[#090C11] border border-white/[0.08] grid grid-cols-2 gap-1">
                <button type="button" @click="regType = 'company_super_admin'"
                    :class="regType === 'company_super_admin' ? 'bg-amber-400 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white font-medium'"
                    class="py-2 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span>New Company (CEO)</span>
                </button>
                <button type="button" @click="regType = 'employee'"
                    :class="regType === 'employee' ? 'bg-amber-400 text-slate-950 font-bold shadow-sm' : 'text-slate-400 hover:text-white font-medium'"
                    class="py-2 px-3 rounded-lg text-xs transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>Join Existing Team</span>
                </button>
            </div>

            <!-- Contextual Info Badge -->
            <div class="mb-4 py-2 px-3 rounded-lg bg-amber-500/[0.08] border border-amber-500/20 text-[11px] text-amber-200/90 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span x-show="regType === 'company_super_admin'">Creates an independent company workspace. Reviewed by Platform Developer.</span>
                <span x-show="regType === 'employee'" x-cloak>Connects to your company workspace. Reviewed by your Company Super Admin.</span>
            </div>

            <form class="space-y-3.5" action="{{ route('register.post') }}" method="POST">
                @csrf
                <input type="hidden" name="registration_type" :value="regType">

                <!-- Company Details Conditional -->
                <div x-show="regType === 'company_super_admin'">
                    <label for="company_name" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Company / Organization Name <span class="text-amber-400">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <input id="company_name" name="company_name" type="text"
                            :required="regType === 'company_super_admin'"
                            value="{{ old('company_name') }}"
                            placeholder="e.g. Apex Global Technologies"
                            class="w-full pl-9 pr-3.5 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                    </div>
                </div>

                <div x-show="regType === 'employee'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="company_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Organization <span class="text-amber-400">*</span>
                        </label>
                        <select id="company_id" name="company_id"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                            <option value="">-- Choose Company --</option>
                            @foreach ($companies as $comp)
                                <option value="{{ $comp->id }}" {{ old('company_id') == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="department_id" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Department
                        </label>
                        <select id="department_id" name="department_id"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                            <option value="">-- Select Team --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Personal Information (2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="name" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Full Name <span class="text-amber-400">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}"
                                placeholder="Elena Rostova"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Work Email <span class="text-amber-400">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </div>
                            <input id="email" name="email" type="email" required value="{{ old('email') }}"
                                placeholder="name@company.com"
                                class="w-full pl-9 pr-3.5 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                        </div>
                    </div>
                </div>

                <!-- Designation & Phone (2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="title" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Job Title
                        </label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}"
                            placeholder="e.g. Lead Engineer / CEO"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                    </div>

                    <div>
                        <label for="phone" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Phone (Optional)
                        </label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                            placeholder="+1 (555) 000-0000"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                    </div>
                </div>

                <!-- Password & Confirm (2 Columns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="password" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Password <span class="text-amber-400">*</span>
                        </label>
                        <input id="password" name="password" type="password" required
                            placeholder="Min 8 characters"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                            Confirm Password <span class="text-amber-400">*</span>
                        </label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                            placeholder="Confirm password"
                            class="w-full px-3 py-2.5 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                    </div>
                </div>

                <!-- Notes / Context (Compact) -->
                <div>
                    <label for="registration_notes" class="block text-[11px] font-bold uppercase tracking-wider text-slate-300 mb-1">
                        Context / Notes (Optional)
                    </label>
                    <input id="registration_notes" name="registration_notes" type="text"
                        value="{{ old('registration_notes') }}"
                        placeholder="Brief note about your workspace or department..."
                        class="w-full px-3 py-2 bg-[#090C11]/90 border border-white/[0.1] rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-amber-400 focus:border-amber-400 transition-all text-xs font-medium">
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-950 bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 shadow-[0_4px_16px_rgba(245,158,11,0.3)] hover:shadow-[0_6px_22px_rgba(245,158,11,0.45)] hover:scale-[1.01] active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span x-text="regType === 'company_super_admin' ? 'Submit Company Request for Developer Approval' : 'Submit Staff Request for Super Admin Approval'"></span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Bottom Switch Link -->
            <div class="mt-4 pt-3.5 border-t border-white/[0.08] text-center">
                <p class="text-xs text-slate-400">
                    Already have an approved account?
                    <a href="{{ route('login') }}" class="font-bold text-amber-400 hover:text-amber-300 transition-colors ml-1 inline-flex items-center gap-0.5">
                        <span>Sign In</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </p>
            </div>
        </div>

        <!-- Trust Badges -->
        <div class="mt-4 flex items-center justify-center gap-3 text-[10px] text-slate-500 font-medium">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-amber-500/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span>Enterprise Multi-Tenant Isolated</span>
            </span>
            <span class="w-1 h-1 rounded-full bg-slate-800"></span>
            <span>256-Bit SSL Encrypted</span>
        </div>
    </div>
</div>
@endsection
