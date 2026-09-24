@extends('layouts.app')

@section('title', 'Enterprise Calendar Hub')

@section('content')
<div class="space-y-6" x-data="schedioCalendar()">

    <!-- Top Action Bar & Filter Controls -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900/80 backdrop-blur-md p-4 sm:p-5 rounded-2xl border border-slate-800/80 shadow-xl">
        <div class="space-y-1">
            <h1 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                <span>Calendar Workspace</span>
                <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-500/15 text-indigo-300 border border-indigo-500/30 font-mono font-medium">FULLCALENDAR v6</span>
            </h1>
            <p class="text-xs text-slate-400">
                Interactive schedule for internal scrums, department workshops, meeting rooms, and client reviews.
            </p>
        </div>

        <!-- Filter Controls -->
        <div class="flex flex-wrap items-center gap-3">
            <!-- Type Selector -->
            <select x-model="filters.type" @change="refetchEvents()"
                class="px-3 py-2 rounded-xl bg-slate-950/80 border border-slate-700/80 text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="all">All Events & Meetings</option>
                <option value="meetings">Meetings Only</option>
                <option value="events">Department Events</option>
                <option value="holidays">Company Holidays</option>
            </select>

            <!-- Department Filter -->
            <select x-model="filters.department_id" @change="refetchEvents()"
                class="px-3 py-2 rounded-xl bg-slate-950/80 border border-slate-700/80 text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>

            <!-- Room Filter -->
            <select x-model="filters.room_id" @change="refetchEvents()"
                class="px-3 py-2 rounded-xl bg-slate-950/80 border border-slate-700/80 text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">All Rooms & Suites</option>
                @foreach ($rooms as $r)
                    <option value="{{ $r->id }}">{{ $r->name }}</option>
                @endforeach
            </select>

            <!-- Quick Create Button -->
            <button @click="openCreateModal()"
                class="px-4 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Schedule Session</span>
            </button>
        </div>
    </div>

    <!-- Calendar Container -->
    <div class="bg-slate-900/70 border border-slate-800/80 rounded-3xl p-4 sm:p-6 shadow-2xl overflow-hidden relative min-h-[700px]">
        <!-- FullCalendar Hook -->
        <div id="calendar" class="w-full"></div>
    </div>

    <!-- 1. Event Details Modal -->
    <div x-show="detailsModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div @click.outside="detailsModalOpen = false"
            class="bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-5 overflow-hidden">
            
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                            :class="activeEvent.extendedProps?.entity_type === 'meeting' ? 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'"
                            x-text="activeEvent.extendedProps?.entity_type === 'meeting' ? (activeEvent.extendedProps?.meeting_type || 'Meeting') : (activeEvent.extendedProps?.category || 'Event')">
                        </span>
                        <template x-if="activeEvent.extendedProps?.is_private">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/30">Private</span>
                        </template>
                    </div>
                    <h3 class="text-xl font-bold text-white" x-text="activeEvent.title"></h3>
                </div>

                <button @click="detailsModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Time & Location Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-950/60 p-3.5 rounded-xl border border-slate-800">
                <div>
                    <span class="text-slate-500 block">Start Time</span>
                    <span class="text-slate-200 font-medium" x-text="formatDate(activeEvent.start)"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">End Time</span>
                    <span class="text-slate-200 font-medium" x-text="formatDate(activeEvent.end)"></span>
                </div>
                <template x-if="activeEvent.extendedProps?.room">
                    <div class="sm:col-span-2 pt-1 border-t border-slate-800 flex items-center gap-2">
                        <span class="text-slate-500">Suite / Room:</span>
                        <span class="text-indigo-400 font-semibold flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: activeEvent.extendedProps?.room?.color || '#6366f1' }"></span>
                            <span x-text="activeEvent.extendedProps?.room?.name"></span>
                            <span class="text-slate-500 font-normal" x-text="'(' + activeEvent.extendedProps?.room?.location + ')'"></span>
                        </span>
                    </div>
                </template>
            </div>

            <!-- Description -->
            <template x-if="activeEvent.extendedProps?.description">
                <div class="text-xs text-slate-300 bg-slate-950/40 p-3 rounded-xl border border-slate-800/80 leading-relaxed">
                    <span class="text-slate-500 font-semibold block mb-0.5">Description / Agenda</span>
                    <p x-text="activeEvent.extendedProps?.description"></p>
                </div>
            </template>

            <!-- Video Meeting Link -->
            <template x-if="activeEvent.extendedProps?.meeting_link">
                <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-950/40 border border-indigo-500/30 text-xs">
                    <div class="flex items-center gap-2 text-indigo-300">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        <span class="truncate max-w-xs font-mono" x-text="activeEvent.extendedProps?.meeting_link"></span>
                    </div>
                    <a :href="activeEvent.extendedProps?.meeting_link" target="_blank"
                        class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-lg text-xs shrink-0 transition-colors">
                        Open Video
                    </a>
                </div>
            </template>

            <!-- Participants list (if meeting) -->
            <template x-if="activeEvent.extendedProps?.participants && activeEvent.extendedProps?.participants.length > 0">
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Participants & RSVP</span>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="p in activeEvent.extendedProps?.participants" :key="p.id">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-800/80 border border-slate-700 text-xs text-slate-300">
                                <span class="w-1.5 h-1.5 rounded-full" :class="p.status === 'accepted' ? 'bg-emerald-400' : (p.status === 'declined' ? 'bg-rose-400' : 'bg-amber-400')"></span>
                                <span x-text="p.name"></span>
                                <span class="text-[10px] text-slate-500" x-text="'(' + p.role + ')'"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Actions (Edit/Delete) -->
            <div class="flex items-center justify-between pt-4 border-t border-slate-800">
                <template x-if="activeEvent.extendedProps?.can_delete">
                    <button @click="deleteActiveEvent()"
                        class="px-3 py-2 rounded-xl bg-rose-950/60 hover:bg-rose-900/60 border border-rose-800/60 text-rose-300 text-xs font-semibold transition-all cursor-pointer">
                        Delete Session
                    </button>
                </template>
                <div class="flex-1"></div>
                <button @click="detailsModalOpen = false"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Create Event / Meeting Modal -->
    <div x-show="createModalOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div @click.outside="createModalOpen = false"
            class="bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl max-w-xl w-full p-6 sm:p-7 space-y-5 max-h-[90vh] overflow-y-auto">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white">Schedule New Session</h3>
                    <p class="text-xs text-slate-400">Book meeting rooms, assign participants, or log company milestones.</p>
                </div>
                <button @click="createModalOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Error Banner -->
            <div x-show="createError" x-cloak class="p-3.5 rounded-xl bg-rose-950/60 border border-rose-500/30 text-rose-300 text-xs" x-text="createError"></div>

            <form @submit.prevent="submitCreateForm()" class="space-y-4">
                <!-- Entity Type Tabs -->
                <div class="flex items-center gap-2 p-1 bg-slate-950 rounded-xl border border-slate-800">
                    <button type="button" @click="formData.entity_type = 'meeting'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-all"
                        :class="formData.entity_type === 'meeting' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                        Meeting / Review
                    </button>
                    <button type="button" @click="formData.entity_type = 'event'"
                        class="flex-1 py-1.5 rounded-lg text-xs font-semibold transition-all"
                        :class="formData.entity_type === 'event' ? 'bg-indigo-600 text-white shadow' : 'text-slate-400 hover:text-white'">
                        Calendar Event / Holiday
                    </button>
                </div>

                <!-- Title -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Session Title <span class="text-rose-400">*</span></label>
                    <input type="text" x-model="formData.title" required placeholder="e.g. Q4 Sprint Architecture & Index Review"
                        class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Date & Time Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Start Time <span class="text-rose-400">*</span></label>
                        <input type="datetime-local" x-model="formData.start_time" required
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">End Time <span class="text-rose-400">*</span></label>
                        <input type="datetime-local" x-model="formData.end_time" required
                            class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <!-- Meeting Specific Fields -->
                <template x-if="formData.entity_type === 'meeting'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Meeting Suite / Room</label>
                                <select x-model="formData.room_id"
                                    class="w-full px-3 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- No Room / Virtual Only --</option>
                                    @foreach ($rooms as $r)
                                        <option value="{{ $r->id }}">{{ $r->name }} (Cap: {{ $r->capacity }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Format</label>
                                <select x-model="formData.type"
                                    class="w-full px-3 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="in_person">In Person</option>
                                    <option value="hybrid">Hybrid</option>
                                    <option value="virtual">Virtual Only</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Video Meeting URL</label>
                            <input type="url" x-model="formData.meeting_link" placeholder="https://meet.schedio.com/room-link"
                                class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Invite Team Members</label>
                            <div class="max-h-32 overflow-y-auto p-2 bg-slate-950/80 border border-slate-700/80 rounded-xl space-y-1">
                                @foreach ($users as $u)
                                    @if ($u->id != auth()->id())
                                        <label class="flex items-center gap-2 p-1.5 hover:bg-slate-800/60 rounded-lg text-xs text-slate-300 cursor-pointer">
                                            <input type="checkbox" value="{{ $u->id }}" x-model="formData.participant_ids"
                                                class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                            <span>{{ $u->name }}</span>
                                            <span class="text-[10px] text-slate-500 font-mono">({{ $u->email }})</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Event Specific Fields -->
                <template x-if="formData.entity_type === 'event'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Category</label>
                                <select x-model="formData.category"
                                    class="w-full px-3 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="general">General</option>
                                    <option value="company_holiday">Company Holiday</option>
                                    <option value="milestone">Milestone / Launch</option>
                                    <option value="training">Training / Workshop</option>
                                    <option value="deadline">Sprint Deadline</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Accent Color</label>
                                <div class="flex items-center gap-2 pt-1">
                                    <template x-for="c in ['#6366f1', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6']" :key="c">
                                        <button type="button" @click="formData.color = c"
                                            class="w-6 h-6 rounded-full border-2 transition-transform"
                                            :class="formData.color === c ? 'scale-125 border-white' : 'border-transparent'"
                                            :style="{ backgroundColor: c }">
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        @if (auth()->user()->isSuperAdmin())
                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" id="is_company_wide" x-model="formData.is_company_wide"
                                    class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                                <label for="is_company_wide" class="text-xs text-slate-300 font-medium cursor-pointer">
                                    Broadcast as Organization-Wide Event
                                </label>
                            </div>
                        @endif
                    </div>
                </template>

                <!-- Description -->
                <div>
                    <label class="block text-xs font-semibold uppercase text-slate-300 mb-1">Agenda / Description</label>
                    <textarea x-model="formData.description" rows="2" placeholder="Key topics or preparation notes..."
                        class="w-full px-4 py-2 bg-slate-950/80 border border-slate-700/80 rounded-xl text-slate-100 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none resize-none"></textarea>
                </div>

                <!-- Submit Buttons -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                    <button type="button" @click="createModalOpen = false"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition-all">
                        Cancel
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-semibold shadow-lg shadow-indigo-600/30 transition-all cursor-pointer disabled:opacity-50">
                        <span x-text="submitting ? 'Saving Session...' : 'Save & Publish Session'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('schedioCalendar', () => ({
        calendar: null,
        detailsModalOpen: false,
        createModalOpen: false,
        submitting: false,
        createError: null,
        activeEvent: {},
        filters: {
            type: 'all',
            department_id: '',
            room_id: '',
        },
        formData: {
            entity_type: 'meeting',
            title: '',
            description: '',
            start_time: '',
            end_time: '',
            department_id: '{{ auth()->user()->department_id }}',
            room_id: '',
            type: 'in_person',
            meeting_link: '',
            category: 'general',
            color: '#6366f1',
            is_company_wide: false,
            participant_ids: [],
        },

        init() {
            const calendarEl = document.getElementById('calendar');
            const { Calendar, dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin } = window.FullCalendar;

            this.calendar = new Calendar(calendarEl, {
                plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                },
                buttonText: {
                    today: 'Today',
                    dayGridMonth: 'Month',
                    timeGridWeek: 'Week',
                    timeGridDay: 'Day',
                    listWeek: 'Agenda',
                },
                editable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                nowIndicator: true,
                events: (info, successCallback, failureCallback) => {
                    const params = new URLSearchParams({
                        start: info.startStr,
                        end: info.endStr,
                        type: this.filters.type,
                        department_id: this.filters.department_id,
                        room_id: this.filters.room_id,
                    });

                    axios.get(`/calendar/feed?${params.toString()}`)
                        .then(res => successCallback(res.data))
                        .catch(err => {
                            console.error('Failed to load events:', err);
                            failureCallback(err);
                        });
                },
                select: (info) => {
                    this.openCreateModalWithRange(info.startStr, info.endStr);
                },
                eventClick: (info) => {
                    this.activeEvent = {
                        id: info.event.id,
                        title: info.event.title,
                        start: info.event.start,
                        end: info.event.end || info.event.start,
                        allDay: info.event.allDay,
                        extendedProps: info.event.extendedProps,
                    };
                    this.detailsModalOpen = true;
                },
                eventDrop: (info) => {
                    this.handleEventTimeChange(info);
                },
                eventResize: (info) => {
                    this.handleEventTimeChange(info);
                },
            });

            this.calendar.render();
        },

        refetchEvents() {
            if (this.calendar) {
                this.calendar.refetchEvents();
            }
        },

        openCreateModal() {
            const now = new Date();
            const later = new Date(now.getTime() + 60 * 60 * 1000);
            this.formData.start_time = this.toLocalISO(now);
            this.formData.end_time = this.toLocalISO(later);
            this.formData.title = '';
            this.formData.description = '';
            this.formData.participant_ids = [];
            this.createError = null;
            this.createModalOpen = true;
        },

        openCreateModalWithRange(startStr, endStr) {
            this.openCreateModal();
            const start = new Date(startStr);
            let end = new Date(endStr);
            if (startStr.length <= 10) {
                // Day clicked
                start.setHours(9, 0, 0);
                end = new Date(start.getTime() + 60 * 60 * 1000);
            }
            this.formData.start_time = this.toLocalISO(start);
            this.formData.end_time = this.toLocalISO(end);
        },

        toLocalISO(date) {
            const pad = (n) => String(n).padStart(2, '0');
            return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
        },

        formatDate(date) {
            if (!date) return 'N/A';
            const d = new Date(date);
            return d.toLocaleString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },

        handleEventTimeChange(info) {
            const type = info.event.extendedProps.entity_type;
            const id = info.event.extendedProps.entity_id;
            const start = info.event.start.toISOString();
            const end = (info.event.end || new Date(info.event.start.getTime() + 3600000)).toISOString();

            axios.patch(`/calendar/${type}/${id}/time`, {
                start_time: start,
                end_time: end,
            })
            .then(res => {
                // Success
            })
            .catch(err => {
                info.revert();
                alert(err.response?.data?.message || 'Failed to update event schedule due to permission or room conflict.');
            });
        },

        submitCreateForm() {
            this.submitting = true;
            this.createError = null;

            axios.post('/calendar', this.formData)
                .then(res => {
                    this.createModalOpen = false;
                    this.submitting = false;
                    this.refetchEvents();
                })
                .catch(err => {
                    this.submitting = false;
                    this.createError = err.response?.data?.message || 'Failed to schedule session. Please check inputs.';
                });
        },

        deleteActiveEvent() {
            if (!confirm('Are you sure you want to delete/cancel this session?')) return;

            const type = this.activeEvent.extendedProps?.entity_type;
            const id = this.activeEvent.extendedProps?.entity_id;

            axios.delete(`/calendar/${type}/${id}`)
                .then(res => {
                    this.detailsModalOpen = false;
                    this.refetchEvents();
                })
                .catch(err => {
                    alert(err.response?.data?.message || 'Failed to delete event.');
                });
        },
    }));
});
</script>
@endpush
@endsection
