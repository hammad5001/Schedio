<div x-data="schedioAiCopilot()"
     x-init="init()"
     @keydown.escape.window="isOpen = false"
     class="relative z-50">

    <!-- ========================================================================= -->
    <!-- Ultra-Luxury Chromatic Floating Trigger Button (Bottom-Right)              -->
    <!-- ========================================================================= -->
    <div class="fixed bottom-6 right-6 z-50">
        <button type="button"
                @click="toggleDrawer()"
                :class="isOpen ? 'ring-2 ring-violet-400/50 scale-95 shadow-[0_0_30px_rgba(139,92,246,0.35)]' : 'shadow-[0_12px_35px_-5px_rgba(0,0,0,0.85),0_0_20px_rgba(245,158,11,0.25)] hover:shadow-[0_12px_40px_-5px_rgba(139,92,246,0.4),0_0_25px_rgba(244,63,94,0.3)]'"
                class="group relative inline-flex items-center p-[1.5px] rounded-full overflow-hidden transition-all duration-300 hover:scale-[1.04] active:scale-95 cursor-pointer">

            <!-- Animated Iridescent Chromatic Border Aura (Amber -> Rose -> Electric Violet) -->
            <span class="absolute inset-0 rounded-full bg-gradient-to-r from-amber-400 via-rose-500 to-violet-600 opacity-85 group-hover:opacity-100 transition-opacity duration-300 animate-pulse"></span>

            <!-- Inner Smoked Obsidian Glass Capsule -->
            <span class="relative flex items-center gap-2.5 px-4 py-2.5 rounded-full bg-[#0d1017]/92 hover:bg-[#0d1017]/80 backdrop-blur-2xl transition-all duration-300">
                <!-- Radiant AI Prism Glyph with Ambient Glow -->
                <span class="relative flex items-center justify-center">
                    <span class="absolute -inset-1 rounded-full bg-gradient-to-r from-amber-400 via-rose-500 to-violet-600 opacity-50 blur-[5px] group-hover:opacity-85 transition-opacity duration-300"></span>
                    <svg class="relative w-4 h-4 transition-transform duration-500 group-hover:rotate-45" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" fill="url(#schedio-ai-gem-btn)" />
                        <circle cx="12" cy="12" r="2.2" fill="#ffffff" />
                        <defs>
                            <linearGradient id="schedio-ai-gem-btn" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#F59E0B" />
                                <stop offset="0.5" stop-color="#F43F5E" />
                                <stop offset="1" stop-color="#8B5CF6" />
                            </linearGradient>
                        </defs>
                    </svg>
                </span>

                <!-- Typography -->
                <span class="text-xs font-extrabold tracking-tight bg-gradient-to-r from-white via-slate-100 to-amber-200 bg-clip-text text-transparent font-sans">
                    Schedio AI
                </span>

                <!-- Holographic Tag -->
                <span class="text-[9px] font-mono font-bold tracking-wider px-1.5 py-0.5 rounded-full bg-gradient-to-r from-violet-500/25 to-amber-500/25 border border-violet-400/40 text-amber-200 shadow-xs">
                    COPILOT
                </span>

                <!-- Keyboard Shortcut Tag -->
                <span class="hidden sm:inline-flex items-center text-[9px] font-mono text-slate-400 group-hover:text-slate-200 border border-white/10 rounded px-1.5 py-0.5 transition-colors">
                    Ctrl+/
                </span>
            </span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- Executive AI Drawer / Floating Modal                                      -->
    <!-- ========================================================================= -->
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-22 right-4 sm:right-6 w-[calc(100vw-2rem)] sm:w-[440px] max-h-[84vh] h-[660px] flex flex-col rounded-3xl bg-[#14171d]/95 border border-white/10 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] backdrop-blur-2xl overflow-hidden z-50">

        <!-- Top Header Bar with Accent Glow -->
        <div class="relative px-5 py-4 bg-gradient-to-b from-[#1b1f27] to-[#14171d] border-b border-white/10 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <!-- Luxury Logo Squircle -->
                <div class="relative w-9 h-9 rounded-xl p-[1.5px] bg-gradient-to-br from-amber-400 via-rose-500 to-violet-600 shadow-lg shadow-rose-500/20 flex items-center justify-center">
                    <div class="w-full h-full rounded-[10px] bg-[#0d1017] flex items-center justify-center">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                            <path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" fill="url(#schedio-ai-gem-hdr)" />
                            <circle cx="12" cy="12" r="2.2" fill="#ffffff" />
                            <defs>
                                <linearGradient id="schedio-ai-gem-hdr" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#F59E0B" />
                                    <stop offset="0.5" stop-color="#F43F5E" />
                                    <stop offset="1" stop-color="#8B5CF6" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h4 class="text-xs font-extrabold text-white tracking-wide font-sans">Schedio Copilot</h4>
                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/25 font-mono">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            Live Workspace
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-0.5 font-medium">Executive Calendar & Workspace Intelligence</p>
                </div>
            </div>

            <!-- Header Action Controls -->
            <div class="flex items-center gap-1.5">
                <!-- Clear History -->
                <button type="button"
                        @click="clearChat()"
                        title="Conversation clear karein"
                        class="p-2 rounded-xl text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>

                <!-- Close Drawer -->
                <button type="button"
                        @click="isOpen = false"
                        title="Close Copilot"
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-white/10 border border-transparent hover:border-white/10 transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Chat Stream Body -->
        <div x-ref="messagesContainer"
             class="flex-1 p-4 sm:p-5 overflow-y-auto space-y-4 text-xs custom-scrollbar">

            <!-- Hero Welcome Card (Always visible at start) -->
            <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900/90 to-slate-950/80 border border-white/5 space-y-2.5 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <span class="text-xs font-bold text-white">Salam {{ Auth::user()->name }}! 👋</span>
                    </div>
                    <span class="text-[10px] font-mono uppercase tracking-wider text-amber-400/90 px-2 py-0.5 rounded-md bg-amber-400/10 border border-amber-400/20">
                        {{ Auth::user()->role_label }}
                    </span>
                </div>
                <p class="text-slate-300 text-[11px] leading-relaxed">
                    Main **Schedio Copilot** hoon. Main aapke calendar, scheduled meetings, free rooms, CRM pipeline, aur workspace ke har feature me madad ke liye tayyar hoon.
                </p>
                <div class="text-[10px] text-slate-400 pt-1 flex items-center gap-1.5 font-medium">
                    <span class="text-amber-400">⚡</span>
                    <span>Neeche diye gaye quick buttons dabayein ya koi bhi sawal poochiye:</span>
                </div>
            </div>

            <!-- Dynamic Messages List -->
            <template x-for="(msg, index) in messages" :key="index">
                <div class="space-y-1">
                    <!-- User Message Bubble -->
                    <template x-if="msg.role === 'user'">
                        <div class="flex items-end justify-end">
                            <div class="max-w-[85%] bg-gradient-to-br from-amber-500/25 to-amber-600/15 border border-amber-500/40 text-amber-100 rounded-2xl rounded-br-xs px-4 py-3 leading-relaxed text-[11px] shadow-sm">
                                <span class="whitespace-pre-wrap font-medium" x-text="msg.content"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Assistant / Copilot Message Card -->
                    <template x-if="msg.role === 'model'">
                        <div class="flex items-start gap-2.5 pt-1">
                            <!-- Small Accent Avatar -->
                            <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-violet-500/20 via-rose-500/20 to-amber-500/20 border border-violet-400/30 flex items-center justify-center text-amber-300 text-[10px] font-bold flex-shrink-0 mt-1 shadow-xs">
                                ✦
                            </div>
                            <!-- Response Body -->
                            <div class="flex-1 bg-[#181c23]/90 border border-white/8 rounded-2xl rounded-tl-xs p-4 text-slate-200 leading-relaxed text-[11px] shadow-md space-y-2.5">
                                <div class="ai-rendered-content space-y-2 leading-relaxed" x-html="renderMarkdown(msg.content)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Loading Thinking State -->
            <div x-show="isLoading" class="flex items-center gap-2.5 text-slate-400 text-[11px] pt-1 pl-9">
                <span class="flex gap-1 items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-bounce [animation-delay:0.4s]"></span>
                </span>
                <span class="text-slate-400 font-mono text-[10px]">Copilot is analyzing workspace...</span>
            </div>
        </div>

        <!-- Quick Suggestions Horizontal Carousel -->
        <div class="px-4 py-2.5 bg-[#101318]/90 border-t border-white/5 flex items-center gap-2 overflow-x-auto no-scrollbar">
            <template x-for="(chip, index) in suggestions" :key="index">
                <button type="button"
                        @click="useSuggestion(chip)"
                        class="text-[10px] font-medium whitespace-nowrap px-3 py-1.5 rounded-xl bg-slate-900/90 hover:bg-amber-400/15 border border-white/10 hover:border-amber-400/40 text-slate-300 hover:text-amber-200 transition-all duration-200 flex-shrink-0 cursor-pointer shadow-sm">
                    <span x-text="chip"></span>
                </button>
            </template>
        </div>

        <!-- Executive Input Box Container -->
        <div class="p-3.5 bg-gradient-to-t from-[#0e1014] to-[#14171d] border-t border-white/10">
            <!-- Recording Indicator Banner -->
            <div x-show="isRecording"
                 x-cloak
                 class="mb-2 px-3 py-1.5 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-[10px] flex items-center justify-between animate-pulse">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                    <span class="font-bold">Listening... boliye, meeting details bolen 🎙️</span>
                </span>
                <button type="button" @click="toggleVoiceRecording()" class="text-rose-200 underline text-[9px] hover:text-white cursor-pointer">Stop</button>
            </div>

            <form @submit.prevent="sendMessage()" class="relative flex items-center">
                <input type="text"
                       x-ref="queryInput"
                       x-model="inputQuery"
                       :disabled="isLoading"
                       placeholder="Boliye ya likhein: e.g. 'Schedule sync tomorrow at 3pm with Sarah'..."
                       class="w-full bg-[#1b2029]/90 border border-white/10 focus:border-amber-400/80 rounded-2xl pl-4 pr-20 py-3 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition-all disabled:opacity-50 font-sans shadow-inner">

                <!-- Voice Microphone Button -->
                <button type="button"
                        @click="toggleVoiceRecording()"
                        :class="isRecording ? 'bg-rose-500 text-white animate-pulse shadow-lg shadow-rose-500/50' : 'text-slate-400 hover:text-amber-300 bg-white/5 hover:bg-white/10'"
                        class="absolute right-10.5 w-7 h-7 rounded-xl flex items-center justify-center transition-all duration-200 cursor-pointer"
                        :title="isRecording ? 'Listening... click to stop' : 'Voice se boliye (Microphone)'">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 10v2a7 7 0 0 1-14 0v-2"/>
                        <line x1="12" y1="19" x2="12" y2="22"/>
                    </svg>
                </button>

                <!-- Submit Button Embedded in Input -->
                <button type="submit"
                        :disabled="isLoading || !inputQuery.trim()"
                        class="absolute right-1.5 w-8 h-8 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-black text-xs flex items-center justify-center transition-all duration-200 disabled:opacity-30 disabled:cursor-not-allowed shadow-md shadow-amber-500/25 cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <!-- Bottom Subtitle Bar -->
            <div class="mt-2.5 flex items-center justify-between text-[9px] text-slate-400 px-1 font-mono">
                <span class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    <span>Voice AI Enabled ✦ Schedio Core</span>
                </span>
                <span class="text-slate-400">Press 🎙️ or Enter ↵</span>
            </div>
        </div>
    </div>
</div>

<script>
function schedioAiCopilot() {
    return {
        isOpen: false,
        isLoading: false,
        isRecording: false,
        recognition: null,
        inputQuery: '',
        messages: [],
        suggestions: [
            '📅 Meri upcoming meetings batao',
            '🎙️ Voice se meeting schedule karo',
            '🚪 Free Rooms check karo',
            '🌐 Mera Public Booking link',
            '🔄 Revert feature kaise kaam karta hai?',
        ],

        init() {
            // Hotkey Ctrl + / or Cmd + / to toggle AI drawer
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                    e.preventDefault();
                    this.toggleDrawer();
                }
            });

            // Initialize Web Speech API for voice recognition
            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (SpeechRec) {
                this.recognition = new SpeechRec();
                this.recognition.continuous = false;
                this.recognition.interimResults = true;
                this.recognition.lang = 'en-US';

                this.recognition.onstart = () => {
                    this.isRecording = true;
                };

                this.recognition.onresult = (event) => {
                    let transcript = '';
                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        transcript += event.results[i][0].transcript;
                    }
                    this.inputQuery = transcript;
                };

                this.recognition.onerror = (e) => {
                    console.warn('Speech error:', e);
                    this.isRecording = false;
                };

                this.recognition.onend = () => {
                    this.isRecording = false;
                    if (this.inputQuery.trim()) {
                        this.sendMessage();
                    }
                };
            }
        },

        toggleVoiceRecording() {
            if (!this.recognition) {
                alert('Aapka browser Web Speech Recognition support nahi karta. Baraye meherbani Google Chrome ya Microsoft Edge use karein.');
                return;
            }
            if (this.isRecording) {
                this.recognition.stop();
                this.isRecording = false;
            } else {
                this.inputQuery = '';
                try {
                    this.recognition.start();
                } catch (e) {
                    this.recognition.stop();
                }
            }
        },

        toggleDrawer() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.$nextTick(() => {
                    this.$refs.queryInput?.focus();
                    this.scrollToBottom();
                });
            }
        },

        useSuggestion(chip) {
            if (chip.includes('Voice')) {
                this.toggleVoiceRecording();
                return;
            }
            this.inputQuery = chip.replace(/^[^\w\s]+/, '').trim();
            this.sendMessage();
        },

        isSchedulingIntent(text) {
            const t = text.toLowerCase();
            return t.includes('schedule') || t.includes('meeting rakh') || t.includes('book meeting') || t.includes('room book') || t.includes('sync rakh') || t.includes('call rakh');
        },

        async sendMessage() {
            const query = this.inputQuery.trim();
            if (!query || this.isLoading) return;

            // Push user message to UI
            this.messages.push({ role: 'user', content: query });
            this.inputQuery = '';
            this.isLoading = true;
            this.scrollToBottom();

            // If query is a scheduling request, trigger Quick-Scheduler
            if (this.isSchedulingIntent(query)) {
                try {
                    const response = await fetch("{{ route('ai.quick-schedule') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ prompt: query, auto_create: false }),
                    });

                    const res = await response.json();
                    const d = res.data;

                    if (d && d.parsed) {
                        let text = `### 📅 Smart Meeting Plan Detected:\n\n`
                            + `- **Title:** ${d.parsed.title}\n`
                            + `- **Date & Time:** ${d.parsed.start_time} (${d.parsed.duration_minutes} mins)\n`
                            + `- **Room:** ${d.parsed.room_name || 'Virtual / No Room'}\n`
                            + `- **Participants:** ${d.parsed.participant_names && d.parsed.participant_names.length ? d.parsed.participant_names.join(', ') : 'Organizer only'}\n\n`;

                        if (d.has_conflicts) {
                            text += `⚠️ **Schedule Collision Detected!** Attendees or room are busy at this time.\n\n`;
                            if (d.alternative_slots && d.alternative_slots.length) {
                                text += `**Recommended Alternative Slots:**\n`;
                                d.alternative_slots.slice(0, 3).forEach(s => {
                                    text += `- ⏰ [Select ${s.start_formatted || s.start}](/meetings/create?start=${encodeURIComponent(s.start)})\n`;
                                });
                            }
                        } else {
                            text += `✅ **No Conflicts Found!** Ready to book.\n\n`
                                + `👉 [Nayi Meeting Create Page Par Kholein](/meetings/create?title=${encodeURIComponent(d.parsed.title)}&start_time=${encodeURIComponent(d.parsed.start_time)}&end_time=${encodeURIComponent(d.parsed.end_time)})`;
                        }

                        this.messages.push({ role: 'model', content: text });
                        this.isLoading = false;
                        this.scrollToBottom();
                        return;
                    }
                } catch (e) {
                    console.warn('Quick schedule fallback to chat:', e);
                }
            }

            try {
                const response = await fetch("{{ route('ai.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ message: query }),
                });

                if (!response.ok) {
                    throw new Error('Network error ' + response.status);
                }

                const data = await response.json();
                this.messages.push({ role: 'model', content: data.answer });

                if (data.suggestions && data.suggestions.length > 0) {
                    this.suggestions = data.suggestions;
                }
            } catch (err) {
                console.error(err);
                this.messages.push({
                    role: 'model',
                    content: 'Maazrat! Request process karte waqt koi takneeki masla pesh aya. Baraye meherbani dobara koshish karein.'
                });
            } finally {
                this.isLoading = false;
                this.scrollToBottom();
            }
        },

        async clearChat() {
            if (!confirm('Kya aap AI Copilot ki conversation history clear karna chahte hain?')) {
                return;
            }

            try {
                await fetch("{{ route('ai.clear') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                });
                this.messages = [];
            } catch (err) {
                console.error(err);
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const container = this.$refs.messagesContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            });
        },

        renderMarkdown(raw) {
            if (!raw) return '';

            // Clean any leftover tip artifacts
            let text = raw
                .replace(/(?:\n|>|\s)*💡\s*\*\*Tip:\*\*.*$/msi, '')
                .replace(/(?:\n|>|\s)*💡\s*Tip:.*$/mi, '');

            // Escape HTML
            let html = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');

            // Headers
            html = html.replace(/^### (.*$)/gim, '<h5 class="text-amber-300 font-extrabold text-xs mt-3 mb-1.5 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>$1</h5>');
            html = html.replace(/^## (.*$)/gim, '<h4 class="text-amber-300 font-extrabold text-sm mt-3 mb-2">$1</h4>');

            // Bold & Italic
            html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="text-white font-bold">$1</strong>');
            html = html.replace(/\*(.*?)\*/g, '<em class="text-slate-300">$1</em>');

            // Blockquotes
            html = html.replace(/^&gt; (.*$)/gim, '<div class="p-2.5 my-2 rounded-xl bg-amber-500/10 border-l-2 border-amber-400 text-amber-200 text-[11px] leading-relaxed">$1</div>');

            // Markdown Links [Label](url) -> Styled Interactive Chips
            html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" class="inline-flex items-center gap-1 px-2 py-0.5 my-0.5 rounded-md bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 hover:border-amber-400 text-amber-300 hover:text-amber-200 font-semibold transition-all shadow-xs cursor-pointer"><span>$1</span><svg class="w-2.5 h-2.5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>');

            // Bullet points with * or - (including emojis) -> Structured Micro-Items
            html = html.replace(/^[\*\-] (.*$)/gim, '<div class="flex items-start gap-2 my-1 pl-1 text-slate-200"><span class="text-amber-400 font-bold leading-5 select-none">•</span><span class="flex-1 leading-relaxed">$1</span></div>');

            // Numbered items 1. 2.
            html = html.replace(/^(\d+)\. (.*$)/gim, '<div class="flex items-start gap-2 my-1 pl-1 text-slate-200"><span class="text-amber-400 font-bold font-mono text-[10px] select-none">$1.</span><span class="flex-1 leading-relaxed">$2</span></div>');

            // Line breaks
            html = html.replace(/\n\n/g, '<div class="h-2"></div>');
            html = html.replace(/\n/g, '<br>');

            return html;
        }
    };
}
</script>
