@extends('layouts.app')

@section('title', 'Executive Overview')

@section('content')
<div class="space-y-8">

    <!-- Top Hero / Welcome Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/30 to-slate-900 border border-slate-800/80 p-6 sm:p-8 shadow-2xl shadow-black/40">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ now()->format('l, F j, Y') }} &bull; Schedio Engine Live
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    Good day, {{ $user->name }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-2xl leading-relaxed">
                    @if ($user->isSuperAdmin())
                        Root Super Admin authority active. You have complete operational governance over organization departments, member approvals, meeting suites, and system audits.
                    @elseif ($user->role === 'client')
                        Welcome to your client partner portal. Track scheduled reviews, view milestone events, and collaborate with your project leaders.
                    @else
                        Here is your daily workspace overview. You have <span class="text-indigo-300 font-semibold">{{ $metrics['today_meetings_count'] }} meeting(s)</span> scheduled for today and <span class="text-amber-300 font-semibold">{{ $metrics['pending_tasks'] }} pending action item(s)</span>.
                    @endif
                </p>
            </div>

            <!-- Super Admin Quick Alert or User Status -->
            <div class="flex flex-wrap items-center gap-3">
                @if ($user->isSuperAdmin() && $metrics['pending_requests'] > 0)
                    <a href="{{ route('admin.registration-requests.index') }}"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>{{ $metrics['pending_requests'] }} Pending Registration Request(s)</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <x-metric-card
            title="Today's Schedule"
            :value="$metrics['today_meetings_count']"
            subtitle="Meetings & active sessions today"
            color="indigo">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card
            title="Upcoming Meetings"
            :value="$metrics['upcoming_meetings']"
            subtitle="Internal sprints & client reviews"
            color="purple">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card
            title="Meeting Suites"
            :value="$metrics['available_rooms']"
            subtitle="Equipped conference rooms & pods"
            color="emerald">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </x-slot:icon>
        </x-metric-card>

        <x-metric-card
            title="Pending Action Items"
            :value="$metrics['pending_tasks']"
            subtitle="Across assigned projects"
            color="amber">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </x-slot:icon>
        </x-metric-card>
    </div>

    <!-- Main Content 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Section: Schedule & Upcoming Meetings (2 Cols) -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Today's Schedule -->
            <x-card
                title="Today's Agenda"
                :description="now()->format('l, F j') . ' • Real-time timeline'">
                
                @if ($todayMeetings->isEmpty() && $todayEvents->isEmpty())
                    <div class="py-10 text-center space-y-3">
                        <div class="inline-flex p-3 rounded-2xl bg-slate-800/60 text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-300">No Sessions Scheduled For Today</h4>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Your schedule is currently clear for today. View upcoming meetings below or schedule a new session.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($todayMeetings as $tm)
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/90 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <x-badge variant="indigo" size="sm">{{ strtoupper($tm->type) }}</x-badge>
                                        @if ($tm->department)
                                            <span class="text-xs text-slate-400 font-medium">{{ $tm->department->name }}</span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-bold text-white">{{ $tm->title }}</h4>
                                    <div class="flex items-center gap-4 text-xs text-slate-400">
                                        <span class="text-slate-300 font-mono">{{ $tm->start_time->format('h:i A') }} - {{ $tm->end_time->format('h:i A') }}</span>
                                        @if ($tm->room)
                                            <span class="flex items-center gap-1.5 text-slate-300">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $tm->room->color }}"></span>
                                                {{ $tm->room->name }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    @if ($tm->meeting_link)
                                        <a href="{{ $tm->meeting_link }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition-all">
                                            <span>Join Video</span>
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <!-- Upcoming Meetings (Next 7 Days) -->
            <x-card
                title="Upcoming Meetings & Reviews"
                description="Next 7 days across company departments and client workspaces">
                
                @if ($upcomingMeetings->isEmpty())
                    <div class="py-8 text-center text-xs text-slate-500">
                        No upcoming meetings scheduled.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($upcomingMeetings as $m)
                            <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800/80 hover:border-slate-700/80 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 group">
                                <div class="space-y-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                                            {{ $m->type }}
                                        </span>
                                        @if ($m->department)
                                            <span class="text-xs text-indigo-400 font-medium">{{ $m->department->name }}</span>
                                        @endif
                                        @if ($m->metadata && isset($m->metadata['client_code']))
                                            <x-badge variant="success" size="sm">Client: {{ $m->metadata['client_code'] }}</x-badge>
                                        @endif
                                    </div>

                                    <h4 class="text-sm sm:text-base font-bold text-white group-hover:text-indigo-300 transition-colors">
                                        {{ $m->title }}
                                    </h4>

                                    @if ($m->description)
                                        <p class="text-xs text-slate-400 line-clamp-1 max-w-xl">{{ $m->description }}</p>
                                    @endif

                                    <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-1">
                                        <div class="flex items-center gap-1.5 text-slate-300">
                                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span class="font-medium text-slate-200">{{ $m->start_time->format('D, M d • h:i A') }}</span> - {{ $m->end_time->format('h:i A') }}
                                        </div>

                                        @if ($m->room)
                                            <div class="flex items-center gap-1.5 text-slate-300">
                                                <span class="w-2 h-2 rounded-full" style="background-color: {{ $m->room->color }}"></span>
                                                {{ $m->room->name }}
                                            </div>
                                        @endif

                                        <div class="text-slate-400">
                                            Host: <span class="text-slate-300">{{ $m->organizer->name }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0 flex items-center gap-2">
                                    @if ($m->meeting_link)
                                        <a href="{{ $m->meeting_link }}" target="_blank"
                                            class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" title="Join Video Meeting">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Right Section: Tasks, Rooms & Security Audit (1 Col) -->
        <div class="space-y-8">
            
            <!-- Pending Tasks -->
            <x-card
                title="Action Items & Tasks"
                description="Follow-up items from meeting discussions">
                
                @if ($pendingTasks->isEmpty())
                    <div class="py-6 text-center text-xs text-slate-500">
                        No pending tasks assigned.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($pendingTasks as $t)
                            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <x-badge :variant="$t->priority === 'high' ? 'danger' : 'warning'" size="sm">
                                        {{ strtoupper($t->priority) }}
                                    </x-badge>
                                    <span class="text-[11px] text-slate-400 font-mono">
                                        {{ $t->due_date ? 'Due ' . $t->due_date->format('M d') : 'No due date' }}
                                    </span>
                                </div>
                                <h5 class="text-xs sm:text-sm font-bold text-slate-200 leading-snug">{{ $t->title }}</h5>
                                <div class="text-[11px] text-slate-400 flex items-center justify-between">
                                    <span>Assignee: <span class="text-slate-300">{{ $t->assignee ? $t->assignee->name : 'Unassigned' }}</span></span>
                                    <span class="capitalize text-slate-400">{{ str_replace('_', ' ', $t->status) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <!-- Meeting Suites Status -->
            <x-card
                title="Meeting Suites & Rooms"
                description="Capacity and equipment availability">
                <div class="space-y-3">
                    @foreach ($rooms as $rm)
                        <div class="p-3 rounded-xl bg-slate-950/40 border border-slate-800/80 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-3 h-3 rounded-full shrink-0" style="background-color: {{ $rm->color }}"></div>
                                <div>
                                    <div class="text-xs font-bold text-slate-200">{{ $rm->name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $rm->location }} &bull; Cap: {{ $rm->capacity }}</div>
                                </div>
                            </div>
                            <x-badge variant="success" size="sm">Available</x-badge>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <!-- System Audit Activity Trail -->
            <x-card
                title="System Activity & Audit"
                description="Recent cryptographic and user events">
                <div class="space-y-3">
                    @foreach ($recentActivities as $act)
                        <div class="flex items-start gap-3 text-xs">
                            <div class="w-2 h-2 rounded-full bg-indigo-400 mt-1.5 shrink-0"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-slate-300 leading-relaxed">{{ $act->description }}</p>
                                <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center gap-2">
                                    <span>{{ $act->created_at->diffForHumans() }}</span>
                                    <span>&bull;</span>
                                    <span>IP: {{ $act->ip_address ?? '127.0.0.1' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
