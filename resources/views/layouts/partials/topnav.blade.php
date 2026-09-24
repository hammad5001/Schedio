<header class="h-16 bg-slate-900/80 backdrop-blur-md border-b border-slate-800/80 sticky top-0 z-30 flex items-center justify-between px-4 sm:px-6 lg:px-8">
    <!-- Left: Mobile Menu Trigger & Breadcrumbs -->
    <div class="flex items-center gap-4">
        <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <div class="hidden sm:flex items-center gap-2 text-xs">
            <span class="text-slate-400">Schedio</span>
            <span class="text-slate-600">/</span>
            <span class="font-semibold text-slate-200">
                @if (request()->routeIs('dashboard'))
                    Executive Dashboard
                @elseif (request()->routeIs('admin.registration-requests.*'))
                    Super Admin &bull; Registration Hub
                @else
                    Workspace
                @endif
            </span>
        </div>
    </div>

    <!-- Right: Search, Notification Bell, Profile Menu Dropdown -->
    <div class="flex items-center gap-3">
        <!-- Live System Status Indicator -->
        <div class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700/60 text-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-slate-300 font-medium">PostgreSQL Engine Active</span>
        </div>

        <!-- Notification Area Dropdown -->
        @php
            $reminders = \App\Models\Reminder::where('user_id', auth()->id())->latest()->take(4)->get();
            $unreadCount = \App\Models\Reminder::where('user_id', auth()->id())->where('status', 'pending')->count();
        @endphp
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" @click.outside="open = false"
                class="p-2.5 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 text-slate-300 hover:text-white relative transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                @if ($unreadCount > 0)
                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-amber-400 ring-2 ring-slate-900"></span>
                @endif
            </button>

            <!-- Dropdown Panel -->
            <div x-show="open" x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-80 sm:w-96 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl shadow-black/80 py-3 z-50 overflow-hidden">
                <div class="px-4 pb-2 border-b border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Notifications & Alerts</span>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-mono">{{ $unreadCount }} Unread</span>
                </div>

                <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/80">
                    @forelse ($reminders as $n)
                        <div class="p-3.5 hover:bg-slate-850/50 transition-colors">
                            <div class="flex items-start gap-2.5">
                                <div class="w-2 h-2 rounded-full {{ $n->status === 'pending' ? 'bg-amber-400' : 'bg-slate-600' }} mt-1.5 shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-bold text-slate-200">{{ $n->title }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5 leading-snug">{{ $n->message }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-1">{{ $n->remind_at->diffForHumans() }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-xs text-slate-500">
                            No notifications at this time.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Profile Menu Dropdown -->
        <div class="relative" x-data="{ userMenuOpen: false }">
            <button @click="userMenuOpen = !userMenuOpen" @click.outside="userMenuOpen = false"
                class="flex items-center gap-2 p-1.5 pl-2.5 pr-3 rounded-xl bg-slate-800/80 hover:bg-slate-700/80 border border-slate-700/60 transition-all cursor-pointer">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-[10px]">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="text-xs font-semibold text-slate-200 hidden sm:inline">{{ auth()->user()->name }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <!-- User Menu Panel -->
            <div x-show="userMenuOpen" x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl shadow-black/80 py-2 z-50">
                
                <div class="px-4 py-2 border-b border-slate-800">
                    <div class="text-xs font-bold text-white">{{ auth()->user()->name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono truncate">{{ auth()->user()->email }}</div>
                    <div class="mt-1">
                        @if (auth()->user()->isSuperAdmin())
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Super Admin</span>
                        @elseif (auth()->user()->role === 'client')
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Client Partner</span>
                        @else
                            <span class="text-[10px] font-medium text-slate-400 capitalize">{{ auth()->user()->role }} &bull; {{ auth()->user()->department?->name ?? 'General' }}</span>
                        @endif
                    </div>
                </div>

                @if (auth()->user()->isSuperAdmin())
                    <a href="{{ route('admin.registration-requests.index') }}" class="flex items-center gap-2.5 px-4 py-2 text-xs text-slate-300 hover:text-white hover:bg-slate-800/80 transition-colors">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        <span>Manage Registrations</span>
                    </a>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="pt-1 border-t border-slate-800">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2.5 px-4 py-2 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-950/30 transition-colors text-left cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
