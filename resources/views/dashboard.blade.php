@extends('layouts.app')

@section('title', 'Workspace Dashboard')

@section('content')
<div class="min-h-full flex flex-col">
    <!-- Top Navigation Header -->
    <header class="bg-slate-900/80 backdrop-blur-md border-b border-slate-800/80 sticky top-0 z-30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="p-2 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-xl shadow-md shadow-indigo-500/20 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <span class="font-bold text-lg tracking-tight bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">
                        Schedio
                    </span>
                </a>

                <nav class="hidden md:flex items-center gap-1">
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-white bg-slate-800/90 border border-slate-700/60 shadow-sm">
                        Dashboard
                    </a>
                    @if ($user->isSuperAdmin())
                        <a href="{{ route('admin.registration-requests.index') }}" class="px-3 py-1.5 rounded-lg text-sm text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition-all flex items-center gap-2">
                            Registration Requests
                            @if ($metrics['pending_requests'] > 0)
                                <span class="px-1.5 py-0.5 text-xs font-bold rounded-full bg-amber-500 text-slate-950">
                                    {{ $metrics['pending_requests'] }}
                                </span>
                            @endif
                        </a>
                    @endif
                </nav>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <div class="text-xs font-semibold text-slate-200">{{ $user->name }}</div>
                    <div class="text-[11px] text-slate-400">
                        @if ($user->isSuperAdmin())
                            <span class="text-indigo-400 font-mono font-medium">Super Admin</span>
                        @elseif ($user->role === 'client')
                            <span class="text-emerald-400 font-mono font-medium">Client / External</span>
                        @else
                            <span class="text-slate-400 capitalize">{{ $user->role }}</span> &bull; {{ $user->department ? $user->department->code : 'General' }}
                        @endif
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 rounded-lg bg-slate-800/80 hover:bg-slate-700/80 text-slate-400 hover:text-slate-200 transition-all text-xs font-medium cursor-pointer" title="Sign Out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- Welcome Banner -->
        <div class="relative overflow-hidden p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800/80 shadow-2xl">
            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-medium mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Schedio v1.0 Production Engine Active
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Welcome back, {{ $user->name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-400 max-w-xl">
                        {{ $user->isSuperAdmin() ? 'Complete administrative authority active. You have full oversight over enterprise registrations, room scheduling, and company meetings.' : 'Manage your upcoming meetings, track action items, and collaborate across departments.' }}
                    </p>
                </div>

                @if ($user->isSuperAdmin() && $metrics['pending_requests'] > 0)
                    <div class="shrink-0">
                        <a href="{{ route('admin.registration-requests.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            {{ $metrics['pending_requests'] }} Registration Request(s) Pending Review
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Approved Members</span>
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-white">{{ $metrics['total_users'] }}</div>
                <div class="mt-1 text-[11px] text-slate-500">Across 3 active departments</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Upcoming Meetings</span>
                    <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-white">{{ $metrics['upcoming_meetings'] }}</div>
                <div class="mt-1 text-[11px] text-purple-400 font-medium">Internal & Client QBRs</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Meeting Suites</span>
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-white">{{ $metrics['available_rooms'] }}</div>
                <div class="mt-1 text-[11px] text-slate-500">Boardrooms & client suites</div>
            </div>

            <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800/80 shadow-lg">
                <div class="flex items-center justify-between text-slate-400 text-xs font-semibold uppercase tracking-wider">
                    <span>Action Tasks</span>
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <div class="mt-2 text-2xl font-bold text-white">{{ $metrics['pending_tasks'] }}</div>
                <div class="mt-1 text-[11px] text-amber-400 font-medium">In progress or review</div>
            </div>
        </div>

        <!-- 2 Column Layout: Meetings & Activity / Tasks -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left: Upcoming Meetings (2 cols) -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-white tracking-tight">Scheduled Meetings & Sessions</h2>
                    <span class="text-xs text-slate-400">Next 7 days</span>
                </div>

                <div class="space-y-3">
                    @forelse ($upcomingMeetings as $m)
                        <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                        {{ $m->type }}
                                    </span>
                                    @if ($m->department)
                                        <span class="text-xs text-slate-400 font-medium">{{ $m->department->name }}</span>
                                    @endif
                                </div>
                                <h3 class="text-base font-bold text-white">{{ $m->title }}</h3>
                                <p class="text-xs text-slate-400 line-clamp-1">{{ $m->description }}</p>
                                <div class="flex items-center gap-4 text-xs text-slate-400 pt-1">
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $m->start_time->format('D, M d • h:i A') }} - {{ $m->end_time->format('h:i A') }}
                                    </div>
                                    @if ($m->room)
                                        <div class="flex items-center gap-1 text-slate-300">
                                            <span class="w-2 h-2 rounded-full" style="background-color: {{ $m->room->color }}"></span>
                                            {{ $m->room->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <div class="text-xs text-slate-400">Host: <span class="text-slate-200 font-medium">{{ $m->organizer->name }}</span></div>
                                @if ($m->meeting_link)
                                    <a href="{{ $m->meeting_link }}" target="_blank" class="inline-flex items-center gap-1 mt-2 text-xs text-indigo-400 hover:text-indigo-300 font-medium">
                                        Join Video <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center rounded-2xl bg-slate-900/40 border border-slate-800/60 text-slate-500 text-sm">
                            No meetings currently scheduled.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Right: Action Tasks & Activity Logs (1 col) -->
            <div class="space-y-6">
                <!-- Tasks -->
                <div class="space-y-3">
                    <h2 class="text-lg font-bold text-white tracking-tight">Active Action Items</h2>
                    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 divide-y divide-slate-800">
                        @forelse ($myTasks as $t)
                            <div class="py-3 first:pt-0 last:pb-0 space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold {{ $t->priority === 'high' ? 'text-rose-400' : 'text-amber-400' }} uppercase text-[10px]">
                                        {{ $t->priority }}
                                    </span>
                                    <span class="text-slate-500">{{ $t->due_date ? $t->due_date->format('M d') : 'No due date' }}</span>
                                </div>
                                <h4 class="text-sm font-semibold text-slate-200">{{ $t->title }}</h4>
                                <div class="text-[11px] text-slate-400">
                                    Dept: {{ $t->department ? $t->department->code : 'All' }} &bull; Status: <span class="capitalize text-slate-300">{{ str_replace('_', ' ', $t->status) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="py-4 text-center text-xs text-slate-500">No active tasks pending.</div>
                        @endforelse
                    </div>
                </div>

                <!-- Audit Activity -->
                <div class="space-y-3">
                    <h2 class="text-lg font-bold text-white tracking-tight">Security & Audit Log</h2>
                    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 space-y-3">
                        @foreach ($recentActivities as $act)
                            <div class="flex items-start gap-3 text-xs">
                                <div class="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0"></div>
                                <div>
                                    <div class="text-slate-300">{{ $act->description }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono mt-0.5">{{ $act->created_at->diffForHumans() }} &bull; IP: {{ $act->ip_address }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
@endsection
