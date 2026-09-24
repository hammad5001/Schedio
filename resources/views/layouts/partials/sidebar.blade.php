<aside class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900/95 backdrop-blur-xl border-r border-slate-800/80 flex flex-col justify-between transition-transform duration-300 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">

    <!-- Sidebar Header / Brand -->
    <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
            <div class="p-2 bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/20 group-hover:scale-105 transition-all">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="font-extrabold text-lg tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-300 bg-clip-text text-transparent">
                    Schedio
                </span>
                <div class="text-[10px] text-slate-400 font-mono tracking-wider">ENTERPRISE v1.0</div>
            </div>
        </a>

        <!-- Mobile Close Button -->
        <button @click="sidebarOpen = false" class="lg:hidden p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Navigation Menu -->
    <div class="flex-1 overflow-y-auto px-4 py-5 space-y-6">
        <!-- Main Section -->
        <div class="space-y-1">
            <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-2">
                Workspace
            </div>

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-850/60' }}">
                <svg class="w-4 h-4 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-indigo-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span>Executive Overview</span>
            </a>

            <!-- Meetings Hub -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-850/60 transition-all">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>Meetings & Sessions</span>
                </div>
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-800 text-slate-300">Live</span>
            </a>

            <!-- Meeting Suites & Rooms -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-850/60 transition-all">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <span>Suites & Focus Pods</span>
            </a>

            <!-- Action Items -->
            <a href="{{ route('dashboard') }}"
                class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-850/60 transition-all">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span>Tasks & Action Items</span>
                </div>
            </a>
        </div>

        <!-- Super Admin Section -->
        @if (auth()->user()->isSuperAdmin())
            <div class="space-y-1 pt-2 border-t border-slate-800/80">
                <div class="px-3 text-[10px] font-bold uppercase tracking-wider text-indigo-400 mb-2 flex items-center justify-between">
                    <span>Administration</span>
                    <span class="px-1.5 py-0.2 rounded text-[9px] bg-indigo-500/20 text-indigo-300">ROOT</span>
                </div>

                <a href="{{ route('admin.registration-requests.index') }}"
                    class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('admin.registration-requests.*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'text-slate-300 hover:text-white hover:bg-slate-850/60' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 {{ request()->routeIs('admin.registration-requests.*') ? 'text-white' : 'text-amber-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        <span>Registration Hub</span>
                    </div>
                    @php
                        $pendingCount = \App\Models\User::where('approval_status', 'pending')->where('role', '!=', 'super_admin')->count();
                    @endphp
                    @if ($pendingCount > 0)
                        <span class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-500 text-slate-950">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </a>
            </div>
        @endif
    </div>

    <!-- User Profile & Quick Actions Footer -->
    <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-md text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="truncate">
                    <div class="text-xs font-bold text-slate-200 truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400 truncate">
                        {{ auth()->user()->isSuperAdmin() ? 'Super Admin' : (auth()->user()->role === 'client' ? 'Client Access' : auth()->user()->title ?? 'Member') }}
                    </div>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800/60 transition-colors cursor-pointer" title="Sign Out">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
