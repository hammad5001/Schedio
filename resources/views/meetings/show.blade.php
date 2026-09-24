@extends('layouts.app')

@section('title', $meeting->title . ' — Details')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Top Navigation & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('meetings.index') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1.5 font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            <span>Back to Meetings Listing</span>
        </a>

        @if ($canManage)
            <div class="flex items-center gap-2">
                @if ($meeting->status === 'scheduled')
                    <form action="{{ route('meetings.cancel', $meeting) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this meeting?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-3.5 py-2 rounded-xl bg-amber-950/60 hover:bg-amber-900/60 border border-amber-800/60 text-amber-300 text-xs font-semibold transition-all cursor-pointer">
                            Cancel Meeting
                        </button>
                    </form>
                @endif

                <a href="{{ route('meetings.edit', $meeting) }}"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs font-semibold transition-all flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Edit Session</span>
                </a>

                <form action="{{ route('meetings.destroy', $meeting) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this meeting?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="p-2 rounded-xl bg-rose-950/60 hover:bg-rose-900/60 border border-rose-800/60 text-rose-300 transition-all cursor-pointer" title="Delete Meeting">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-sm flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Main Overview Header Card -->
    <div class="bg-slate-900/80 border border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-2">
                <x-badge :variant="$meeting->status === 'scheduled' ? 'success' : ($meeting->status === 'cancelled' ? 'danger' : 'default')" size="sm">
                    {{ strtoupper($meeting->status) }}
                </x-badge>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-slate-800 text-slate-300 border border-slate-700">
                    {{ str_replace('_', ' ', $meeting->type) }}
                </span>
                @if ($meeting->metadata && isset($meeting->metadata['is_client_meeting']) && $meeting->metadata['is_client_meeting'])
                    <x-badge variant="purple" size="sm">
                        Client Partner Meeting
                    </x-badge>
                @endif
                @if ($meeting->department)
                    <span class="text-xs text-indigo-400 font-medium">{{ $meeting->department->name }}</span>
                @endif
            </div>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                {{ $meeting->title }}
            </h1>

            @if ($meeting->description)
                <p class="text-sm text-slate-300 leading-relaxed max-w-3xl">
                    {{ $meeting->description }}
                </p>
            @endif
        </div>

        <!-- Schedule & Location Spec Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-5 rounded-2xl bg-slate-950/60 border border-slate-800">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Date & Time</span>
                <span class="text-xs font-semibold text-slate-200 mt-1 block">
                    {{ $meeting->start_time->format('l, M d, Y') }}
                </span>
                <span class="text-[11px] text-slate-400 font-mono">
                    {{ $meeting->start_time->format('h:i A') }} &ndash; {{ $meeting->end_time->format('h:i A') }} ({{ $meeting->timezone }})
                </span>
            </div>

            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Venue / Room</span>
                @if ($meeting->room)
                    <div class="flex items-center gap-1.5 mt-1">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $meeting->room->color }}"></span>
                        <span class="text-xs font-bold text-white">{{ $meeting->room->name }}</span>
                    </div>
                    <span class="text-[11px] text-slate-400">{{ $meeting->room->location }} &bull; Cap: {{ $meeting->room->capacity }}</span>
                @else
                    <span class="text-xs text-slate-400 mt-1 block">Virtual / No physical room</span>
                @endif
            </div>

            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Organizer</span>
                <span class="text-xs font-semibold text-white mt-1 block">{{ $meeting->organizer->name }}</span>
                <span class="text-[11px] text-slate-400 font-mono">{{ $meeting->organizer->email }}</span>
            </div>

            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Recurrence</span>
                <span class="text-xs font-semibold text-slate-300 mt-1 block">
                    {{ $meeting->recurrence ? ucfirst($meeting->recurrence['frequency'] ?? 'Weekly') : 'One-time Session' }}
                </span>
                <span class="text-[11px] text-slate-500">
                    {{ $meeting->is_private ? 'Private Meeting' : 'Open Workspace' }}
                </span>
            </div>
        </div>

        <!-- Meeting Video Link Banner -->
        @if ($meeting->meeting_link)
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-2xl bg-gradient-to-r from-indigo-950/60 to-purple-950/40 border border-indigo-500/30">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-xl bg-indigo-600/30 text-indigo-300 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs font-bold text-white">Online Video Conference Link</div>
                        <div class="text-xs text-indigo-300 font-mono truncate max-w-md">{{ $meeting->meeting_link }}</div>
                    </div>
                </div>
                <a href="{{ $meeting->meeting_link }}" target="_blank"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all text-center shrink-0">
                    Join Video Call &rarr;
                </a>
            </div>
        @endif
    </div>

    <!-- 2 Column Layout: Participants & RSVP / Action Items -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Participants & RSVP Submission (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- RSVP Module -->
            <x-card title="Your RSVP Response" description="Let the organizer know if you will be attending this session">
                @php
                    $myParticipantRecord = $meeting->participants->where('id', auth()->id())->first();
                    $currentStatus = $myParticipantRecord ? $myParticipantRecord->pivot->status : 'none';
                @endphp

                <form action="{{ route('meetings.rsvp', $meeting) }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-3 gap-3">
                        <label class="p-3 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all {{ $currentStatus === 'accepted' ? 'bg-emerald-950/40 border-emerald-500 text-emerald-300 ring-1 ring-emerald-500' : 'bg-slate-950/40 border-slate-800 text-slate-400 hover:border-slate-700' }}">
                            <input type="radio" name="status" value="accepted" {{ $currentStatus === 'accepted' ? 'checked' : '' }} class="sr-only">
                            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span class="text-xs font-bold">Accept</span>
                        </label>

                        <label class="p-3 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all {{ $currentStatus === 'tentative' ? 'bg-amber-950/40 border-amber-500 text-amber-300 ring-1 ring-amber-500' : 'bg-slate-950/40 border-slate-800 text-slate-400 hover:border-slate-700' }}">
                            <input type="radio" name="status" value="tentative" {{ $currentStatus === 'tentative' ? 'checked' : '' }} class="sr-only">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-xs font-bold">Tentative</span>
                        </label>

                        <label class="p-3 rounded-xl border flex items-center justify-center gap-2 cursor-pointer transition-all {{ $currentStatus === 'declined' ? 'bg-rose-950/40 border-rose-500 text-rose-300 ring-1 ring-rose-500' : 'bg-slate-950/40 border-slate-800 text-slate-400 hover:border-slate-700' }}">
                            <input type="radio" name="status" value="declined" {{ $currentStatus === 'declined' ? 'checked' : '' }} class="sr-only">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span class="text-xs font-bold">Decline</span>
                        </label>
                    </div>

                    <div>
                        <input type="text" name="response_notes" placeholder="Optional RSVP note to the organizer..." value="{{ $myParticipantRecord?->pivot?->response_notes }}"
                            class="w-full px-4 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-md shadow-indigo-600/20 cursor-pointer">
                            Update RSVP Status
                        </button>
                    </div>
                </form>
            </x-card>

            <!-- Participant Roster -->
            <x-card title="Invited Participants ({{ $meeting->participants->count() }})" description="Live attendance and response status">
                <div class="divide-y divide-slate-800/80">
                    @foreach ($meeting->participants as $p)
                        <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white text-xs">
                                    {{ strtoupper(substr($p->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-200">{{ $p->name }}</div>
                                    <div class="text-[11px] text-slate-500 font-mono">{{ $p->email }} &bull; <span class="capitalize">{{ $p->pivot->role }}</span></div>
                                    @if ($p->pivot->response_notes)
                                        <div class="text-[11px] text-slate-400 italic">"{{ $p->pivot->response_notes }}"</div>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <x-badge :variant="$p->pivot->status === 'accepted' ? 'success' : ($p->pivot->status === 'declined' ? 'danger' : 'warning')" size="sm">
                                    {{ ucfirst($p->pivot->status) }}
                                </x-badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        </div>

        <!-- Right: Tasks & Client Metadata (1 Col) -->
        <div class="space-y-6">

            <!-- Client Metadata (if any) -->
            @if ($meeting->metadata && isset($meeting->metadata['is_client_meeting']) && $meeting->metadata['is_client_meeting'])
                <x-card title="Client Information" description="External stakeholder details">
                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-slate-500 block">Contact Name</span>
                            <span class="text-slate-200 font-bold">{{ $meeting->metadata['client_name'] ?? 'Not specified' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Company / Partner</span>
                            <span class="text-purple-400 font-semibold">{{ $meeting->metadata['client_company'] ?? 'External' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 block">Email Address</span>
                            <span class="text-slate-300 font-mono">{{ $meeting->metadata['client_email'] ?? 'Not provided' }}</span>
                        </div>
                    </div>
                </x-card>
            @endif

            <!-- Follow-up Action Items -->
            <x-card title="Meeting Action Items" description="Tasks derived from this session">
                @if ($meeting->tasks->isEmpty())
                    <div class="py-4 text-center text-xs text-slate-500">
                        No action items linked to this meeting.
                    </div>
                @else
                    <div class="space-y-2.5">
                        @foreach ($meeting->tasks as $t)
                            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 space-y-1">
                                <div class="flex items-center justify-between text-[10px]">
                                    <x-badge :variant="$t->priority === 'high' ? 'danger' : 'warning'" size="sm">{{ strtoupper($t->priority) }}</x-badge>
                                    <span class="text-slate-500 capitalize">{{ str_replace('_', ' ', $t->status) }}</span>
                                </div>
                                <h5 class="text-xs font-bold text-slate-200">{{ $t->title }}</h5>
                                <div class="text-[11px] text-slate-400">
                                    Assignee: <span class="text-slate-300">{{ $t->assignee?->name ?? 'Unassigned' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>
    </div>
</div>
@endsection
