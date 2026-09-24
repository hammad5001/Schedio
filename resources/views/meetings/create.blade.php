@extends('layouts.app')

@section('title', 'Schedule Meeting')

@section('content')
<div class="max-w-4xl mx-auto space-y-6" x-data="meetingScheduler()">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Schedule New Meeting</h1>
            <p class="text-xs text-slate-400">Configure meeting parameters, book meeting suites, invite participants, and check live availability.</p>
        </div>
        <a href="{{ route('meetings.index') }}" class="text-xs text-slate-400 hover:text-white flex items-center gap-1">
            &larr; Back to Meetings
        </a>
    </div>

    @if ($errors->any())
        <div class="p-4 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach ($errors->all() as $error)
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <form action="{{ route('meetings.store') }}" method="POST" class="bg-slate-900/80 border border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-2xl space-y-6">
        @csrf

        <!-- Core Meeting Info -->
        <div class="space-y-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">1. Basic Information</h3>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Meeting Title <span class="text-rose-400">*</span></label>
                <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Q4 Executive Strategy & Engineering Sync"
                    class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Agenda / Description</label>
                <textarea name="description" rows="3" placeholder="Outline agenda items, prerequisites or talking points..."
                    class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none">{{ old('description') }}</textarea>
            </div>
        </div>

        <!-- Date, Time & Timezone with Live Availability Check -->
        <div class="space-y-4 pt-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">2. Time & Timezone</h3>
                <button type="button" @click="fetchSmartSuggestions()"
                    class="text-xs text-indigo-400 hover:text-indigo-300 flex items-center gap-1 font-semibold cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Suggest Available Timeslots</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Start Time <span class="text-rose-400">*</span></label>
                    <input type="datetime-local" name="start_time" x-model="startTime" @change="checkAvailability()" required
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">End Time <span class="text-rose-400">*</span></label>
                    <input type="datetime-local" name="end_time" x-model="endTime" @change="checkAvailability()" required
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Timezone</label>
                    <select name="timezone" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="America/New_York" {{ old('timezone', $user->timezone) === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST)</option>
                        <option value="America/Chicago" {{ old('timezone', $user->timezone) === 'America/Chicago' ? 'selected' : '' }}>America/Chicago (CST)</option>
                        <option value="America/Los_Angeles" {{ old('timezone', $user->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (PST)</option>
                        <option value="UTC" {{ old('timezone', $user->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                        <option value="Europe/London" {{ old('timezone', $user->timezone) === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT)</option>
                        <option value="Asia/Karachi" {{ old('timezone', $user->timezone) === 'Asia/Karachi' ? 'selected' : '' }}>Asia/Karachi (PKT)</option>
                    </select>
                </div>
            </div>

            <!-- Smart Suggestion Panel (Dropdown / Pills) -->
            <div x-show="suggestions.length > 0" x-cloak class="p-3.5 rounded-2xl bg-indigo-950/40 border border-indigo-500/30 space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-indigo-300 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Conflict-Free Time Slots Found:</span>
                    </span>
                    <button type="button" @click="suggestions = []" class="text-slate-400 hover:text-white text-[11px]">Dismiss</button>
                </div>
                <div class="flex flex-wrap gap-2 pt-1">
                    <template x-for="slot in suggestions" :key="slot.start_time">
                        <button type="button" @click="applySlot(slot)"
                            class="px-3 py-1.5 rounded-xl bg-slate-900 border border-indigo-500/40 hover:border-indigo-400 text-xs text-slate-200 hover:text-white font-mono flex items-center gap-1.5 transition-all cursor-pointer">
                            <span x-text="slot.date + ' • ' + slot.formatted_start + ' - ' + slot.formatted_end"></span>
                            <span class="text-[10px] text-emerald-400">&bull; Open</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Recurrence & Reminders -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Recurrence Schedule</label>
                    <select name="recurrence_frequency" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="none">Does Not Repeat (One-off)</option>
                        <option value="daily">Repeats Daily</option>
                        <option value="weekly">Repeats Weekly</option>
                        <option value="biweekly">Repeats Bi-Weekly (Every 2 weeks)</option>
                        <option value="monthly">Repeats Monthly</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Pre-Meeting Reminder</label>
                    <select name="reminder_minutes" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="0">No In-App Reminder</option>
                        <option value="5">5 Minutes Before</option>
                        <option value="10">10 Minutes Before</option>
                        <option value="15" selected>15 Minutes Before</option>
                        <option value="30">30 Minutes Before</option>
                        <option value="60">1 Hour Before</option>
                        <option value="1440">1 Day Before</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Venue, Format & Room Conflict Monitor -->
        <div class="space-y-4 pt-4 border-t border-slate-800">
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">3. Format & Room Allocation</h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Format <span class="text-rose-400">*</span></label>
                    <select name="type" x-model="meetingType" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="in_person">In Person</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="virtual">Virtual Only</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Meeting Room / Suite</label>
                    <select name="room_id" x-model="roomId" @change="checkAvailability()"
                        class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- No Room Reservation --</option>
                        @foreach ($rooms as $r)
                            <option value="{{ $r->id }}" {{ old('room_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} (Cap: {{ $r->capacity }} &bull; {{ $r->location }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Department</label>
                    <select name="department_id" class="w-full px-3 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">General / All Hands</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" {{ old('department_id', $user->department_id) == $d->id ? 'selected' : '' }}>
                                {{ $d->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Room Conflict Warning Box -->
            <template x-if="availability.room && availability.room.status === 'conflict'">
                <div class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/40 text-rose-300 text-xs flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <div>
                        <span class="font-bold">Room Double-Booking Conflict:</span>
                        This room is already reserved for <span class="font-semibold text-white" x-text="availability.room.conflict.title"></span> by <span class="text-slate-200" x-text="availability.room.conflict.organizer"></span>.
                    </div>
                </div>
            </template>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Video Meeting URL</label>
                    <input type="url" name="meeting_link" value="{{ old('meeting_link') }}" placeholder="https://meet.google.com/xyz-abc"
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Custom Location / Address</label>
                    <input type="text" name="location" value="{{ old('location') }}" placeholder="e.g. Boardroom A or Zoom Link"
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Client / External Scope Toggle -->
        <div class="space-y-4 pt-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-purple-400">4. Client / External Partnership Meeting</h3>
                    <p class="text-xs text-slate-400">Enable this section for joint business reviews, vendor syncs, or partner clients.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_client_meeting" value="1" x-model="isClientMeeting" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>

            <div x-show="isClientMeeting" x-cloak class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-purple-950/20 border border-purple-800/40">
                <div>
                    <label class="block text-xs font-semibold uppercase text-purple-300 mb-1">Client Contact Name</label>
                    <input type="text" name="client_name" value="{{ old('client_name') }}" placeholder="e.g. David Sterling"
                        class="w-full px-3.5 py-2 bg-slate-950/80 border border-purple-700/50 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-purple-300 mb-1">Client Email</label>
                    <input type="email" name="client_email" value="{{ old('client_email') }}" placeholder="david@acme.com"
                        class="w-full px-3.5 py-2 bg-slate-950/80 border border-purple-700/50 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase text-purple-300 mb-1">Company / Organization</label>
                    <input type="text" name="client_company" value="{{ old('client_company') }}" placeholder="Acme Corp"
                        class="w-full px-3.5 py-2 bg-slate-950/80 border border-purple-700/50 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-purple-500 focus:outline-none">
                </div>
            </div>
        </div>

        <!-- Participants & Real-time Schedule Conflict Roster -->
        <div class="space-y-4 pt-4 border-t border-slate-800">
            <div class="flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400">5. Participants & Schedule Matrix</h3>
                <span class="text-[11px] text-slate-400">Real-time schedule validation</span>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase text-slate-300 mb-2">Select Team Members</label>
                <div class="max-h-56 overflow-y-auto p-3 bg-slate-950/80 border border-slate-700/80 rounded-2xl grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach ($users as $u)
                        @if ($u->id != auth()->id())
                            <label class="flex items-center justify-between p-2.5 hover:bg-slate-800/60 rounded-xl text-xs text-slate-300 cursor-pointer border border-transparent hover:border-slate-700/60 transition-all">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <input type="checkbox" name="participant_ids[]" value="{{ $u->id }}" x-model="selectedParticipantIds" @change="checkAvailability()"
                                        class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                    <div class="truncate">
                                        <div class="font-semibold text-slate-200 truncate">{{ $u->name }}</div>
                                        <div class="text-[10px] text-slate-500 font-mono truncate">{{ $u->email }}</div>
                                    </div>
                                </div>

                                <!-- Live Availability Badge per user -->
                                <div class="shrink-0 pl-2">
                                    <template x-if="getParticipantStatus({{ $u->id }}) === 'available'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Available</span>
                                    </template>
                                    <template x-if="getParticipantStatus({{ $u->id }}) === 'busy'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30" :title="getParticipantReason({{ $u->id }})">Busy (Event)</span>
                                    </template>
                                    <template x-if="getParticipantStatus({{ $u->id }}) === 'conflict'">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30" :title="getParticipantReason({{ $u->id }})">Conflict</span>
                                    </template>
                                </div>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <input type="checkbox" id="is_private" name="is_private" value="1" {{ old('is_private') ? 'checked' : '' }}
                    class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                <label for="is_private" class="text-xs text-slate-300 font-medium cursor-pointer">
                    Private Meeting (Visible only to organizer and invited participants)
                </label>
            </div>
        </div>

        <!-- Form Submit Actions -->
        <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-800">
            <a href="{{ route('meetings.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all cursor-pointer">
                Schedule & Broadcast Session
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('meetingScheduler', () => ({
        isClientMeeting: false,
        meetingType: 'in_person',
        startTime: '{{ old('start_time', now()->addHour()->format('Y-m-d\TH:00')) }}',
        endTime: '{{ old('end_time', now()->addHours(2)->format('Y-m-d\TH:00')) }}',
        roomId: '{{ old('room_id', '') }}',
        selectedParticipantIds: @json(old('participant_ids', [])),
        availability: {
            has_conflicts: false,
            room: null,
            participants: [],
        },
        suggestions: [],

        init() {
            this.checkAvailability();
        },

        checkAvailability() {
            if (!this.startTime || !this.endTime) return;

            axios.post('{{ route('availability.check') }}', {
                start_time: this.startTime,
                end_time: this.endTime,
                room_id: this.roomId || null,
                participant_ids: this.selectedParticipantIds,
            })
            .then(res => {
                this.availability = res.data;
            })
            .catch(err => {
                console.error('Availability check failed:', err);
            });
        },

        fetchSmartSuggestions() {
            if (!this.startTime) return;

            const date = this.startTime.split('T')[0];

            axios.get('{{ route('availability.suggestions') }}', {
                params: {
                    date: date,
                    room_id: this.roomId || null,
                    participant_ids: this.selectedParticipantIds,
                    duration_minutes: 60,
                }
            })
            .then(res => {
                this.suggestions = res.data.slots || [];
                if (this.suggestions.length === 0) {
                    alert('No conflict-free slots found for standard working hours on this date.');
                }
            })
            .catch(err => {
                console.error('Suggestions query failed:', err);
            });
        },

        applySlot(slot) {
            this.startTime = slot.start_time.substring(0, 16);
            this.endTime = slot.end_time.substring(0, 16);
            this.suggestions = [];
            this.checkAvailability();
        },

        getParticipantStatus(userId) {
            const p = this.availability.participants?.find(item => item.user_id == userId);
            return p ? p.status : null;
        },

        getParticipantReason(userId) {
            const p = this.availability.participants?.find(item => item.user_id == userId);
            return p ? `${p.reason} (${p.conflict_time})` : '';
        },
    }));
});
</script>
@endpush
@endsection
