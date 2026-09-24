@extends('layouts.app')

@section('title', 'Meetings & Sessions Management')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Row -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/80 backdrop-blur-md p-5 rounded-2xl border border-slate-800/80 shadow-xl">
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                <span>Meeting Management Hub</span>
            </h1>
            <p class="text-xs text-slate-400">
                Organize company sprints, client QBRs, manage room bookings and track RSVP responses.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('calendar.index') }}"
                class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Calendar View</span>
            </a>
            <a href="{{ route('meetings.create') }}"
                class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Schedule Meeting</span>
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filters & Search Bar -->
    <div class="bg-slate-900/70 border border-slate-800 rounded-2xl p-4 sm:p-5 shadow-lg space-y-4">
        <form action="{{ route('meetings.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Search Keyword</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search title, description, location..."
                    class="w-full px-3.5 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <!-- Scope -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Scope</label>
                <select name="scope" class="w-full px-3 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="all" {{ $scope === 'all' ? 'selected' : '' }}>All Scopes</option>
                    <option value="internal" {{ $scope === 'internal' ? 'selected' : '' }}>Internal Company</option>
                    <option value="external" {{ $scope === 'external' ? 'selected' : '' }}>Client / External</option>
                    <option value="organized_by_me" {{ $scope === 'organized_by_me' ? 'selected' : '' }}>Organized by Me</option>
                </select>
            </div>

            <!-- Department -->
            <div>
                <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Department</label>
                <select name="department_id" class="w-full px-3 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Submit Filter -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold transition-all cursor-pointer">
                    Filter
                </button>
                <a href="{{ route('meetings.index') }}" class="py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all text-center">
                    Reset
                </a>
            </div>
        </form>

        <!-- Status Pills -->
        <div class="flex items-center gap-2 pt-2 border-t border-slate-800/80 overflow-x-auto text-xs">
            <a href="{{ route('meetings.index', array_merge(request()->query(), ['status' => 'all'])) }}"
                class="px-3 py-1 rounded-lg font-medium transition-all {{ $status === 'all' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800/80 text-slate-400 hover:text-slate-200' }}">
                All ({{ $counts['all'] }})
            </a>
            <a href="{{ route('meetings.index', array_merge(request()->query(), ['status' => 'scheduled'])) }}"
                class="px-3 py-1 rounded-lg font-medium transition-all {{ $status === 'scheduled' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800/80 text-slate-400 hover:text-slate-200' }}">
                Scheduled ({{ $counts['upcoming'] }})
            </a>
            <a href="{{ route('meetings.index', array_merge(request()->query(), ['status' => 'completed'])) }}"
                class="px-3 py-1 rounded-lg font-medium transition-all {{ $status === 'completed' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800/80 text-slate-400 hover:text-slate-200' }}">
                Completed ({{ $counts['completed'] }})
            </a>
            <a href="{{ route('meetings.index', array_merge(request()->query(), ['status' => 'cancelled'])) }}"
                class="px-3 py-1 rounded-lg font-medium transition-all {{ $status === 'cancelled' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800/80 text-slate-400 hover:text-slate-200' }}">
                Cancelled ({{ $counts['cancelled'] }})
            </a>
        </div>
    </div>

    <!-- Meetings Listing -->
    <div class="bg-slate-900/70 border border-slate-800/80 rounded-2xl shadow-xl overflow-hidden">
        @if ($meetings->isEmpty())
            <div class="text-center py-16 px-4 space-y-3">
                <div class="inline-flex p-4 rounded-2xl bg-slate-800/50 text-slate-500">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-300">No Meetings Found</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">No meetings match the selected filters or search terms. Try clearing filters or create a new session.</p>
                <div class="pt-2">
                    <a href="{{ route('meetings.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/25">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Schedule Meeting
                    </a>
                </div>
            </div>
        @else
            <div class="divide-y divide-slate-800/80">
                @foreach ($meetings as $m)
                    <div class="p-5 sm:p-6 hover:bg-slate-850/40 transition-colors flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <!-- Meeting Info -->
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :variant="$m->status === 'scheduled' ? 'success' : ($m->status === 'cancelled' ? 'danger' : 'default')" size="sm">
                                    {{ strtoupper($m->status) }}
                                </x-badge>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                                    {{ str_replace('_', ' ', $m->type) }}
                                </span>
                                @if ($m->metadata && isset($m->metadata['is_client_meeting']) && $m->metadata['is_client_meeting'])
                                    <x-badge variant="purple" size="sm">
                                        Client: {{ $m->metadata['client_company'] ?? ($m->metadata['client_name'] ?? 'External') }}
                                    </x-badge>
                                @endif
                                @if ($m->department)
                                    <span class="text-xs text-indigo-400 font-medium">{{ $m->department->name }}</span>
                                @endif
                                @if ($m->recurrence)
                                    <span class="text-[10px] px-2 py-0.5 rounded bg-sky-500/20 text-sky-300 border border-sky-500/30 flex items-center gap-1 font-mono">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Recurring: {{ ucfirst($m->recurrence['frequency'] ?? 'Weekly') }}
                                    </span>
                                @endif
                            </div>

                            <a href="{{ route('meetings.show', $m) }}" class="text-base sm:text-lg font-bold text-white hover:text-indigo-300 transition-colors block">
                                {{ $m->title }}
                            </a>

                            @if ($m->description)
                                <p class="text-xs text-slate-400 line-clamp-2">{{ $m->description }}</p>
                            @endif

                            <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 pt-1">
                                <div class="flex items-center gap-1.5 text-slate-200">
                                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="font-medium">{{ $m->start_time->format('D, M d, Y • h:i A') }}</span> - {{ $m->end_time->format('h:i A') }}
                                </div>

                                @if ($m->room)
                                    <div class="flex items-center gap-1.5 text-slate-300">
                                        <span class="w-2 h-2 rounded-full" style="background-color: {{ $m->room->color }}"></span>
                                        {{ $m->room->name }} ({{ $m->room->location }})
                                    </div>
                                @endif

                                <div>
                                    Organizer: <span class="text-slate-300 font-medium">{{ $m->organizer->name }}</span>
                                </div>

                                <div>
                                    Participants: <span class="text-slate-300 font-medium">{{ $m->participants->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 shrink-0">
                            @if ($m->meeting_link)
                                <a href="{{ $m->meeting_link }}" target="_blank"
                                    class="px-3.5 py-2 rounded-xl bg-indigo-600/20 hover:bg-indigo-600/30 border border-indigo-500/30 text-indigo-300 text-xs font-semibold transition-all flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    <span>Join</span>
                                </a>
                            @endif

                            <a href="{{ route('meetings.show', $m) }}"
                                class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 transition-all">
                                Details
                            </a>

                            @if (auth()->user()->isSuperAdmin() || $m->organizer_id === auth()->id())
                                <a href="{{ route('meetings.edit', $m) }}"
                                    class="p-2 rounded-xl bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors" title="Edit Meeting">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($meetings->hasPages())
                <div class="p-4 border-t border-slate-800">
                    {{ $meetings->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
