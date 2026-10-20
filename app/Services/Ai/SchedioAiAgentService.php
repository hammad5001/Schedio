<?php

namespace App\Services\Ai;

use App\Models\Meeting;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SchedioAiAgentService
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');

        // Fallback to reading .env directly if process environment was cached/stale
        if (empty($this->apiKey) && file_exists(base_path('.env'))) {
            $env = file_get_contents(base_path('.env'));
            if (preg_match('/^GEMINI_API_KEY=(.+)$/m', $env, $m)) {
                $this->apiKey = trim(trim($m[1]), '"\'');
            }
        }

        $this->model = config('services.gemini.model') ?: env('GEMINI_MODEL', 'gemini-3.5-flash-lite');

        if (empty($this->model) && file_exists(base_path('.env'))) {
            $env = file_get_contents(base_path('.env'));
            if (preg_match('/^GEMINI_MODEL=(.+)$/m', $env, $m)) {
                $this->model = trim(trim($m[1]), '"\'');
            }
        }
    }

    /**
     * Process a user query through the Schedio AI Copilot.
     *
     * @param User $user
     * @param string $message
     * @param array $history
     * @return array{answer: string, suggestions: array<string>}
     */
    public function chat(User $user, string $message, array $history = []): array
    {
        $liveContext = $this->buildLiveUserContext($user);
        $systemInstructions = $this->buildSystemInstructions($user, $liveContext);

        // If Gemini API Key is configured, attempt generative completion
        if (!empty($this->apiKey)) {
            try {
                $response = $this->callGeminiApi($systemInstructions, $message, $history);
                if ($response !== null) {
                    return [
                        'answer' => $response,
                        'suggestions' => $this->getDynamicSuggestions($user),
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, switching to local knowledge engine: ' . $e->getMessage());
            }
        }

        // Offline / Fallback Intelligent Knowledge Base Engine
        $localAnswer = $this->resolveLocalKnowledge($user, $message, $liveContext);

        return [
            'answer' => $localAnswer,
            'suggestions' => $this->getDynamicSuggestions($user),
        ];
    }

    /**
     * Call the Google Gemini API with system instructions and chat history.
     */
    protected function callGeminiApi(string $systemInstructions, string $message, array $history): ?string
    {
        // Fast candidate models pool with instantaneous response time and fallback
        $candidateModels = array_values(array_unique(array_filter([
            'gemini-3.5-flash-lite',
            'gemini-flash-lite-latest',
            $this->model,
            'gemini-3.8-flash',
            'gemini-flash-latest',
        ])));

        // Format history into Gemini format
        $contents = [];

        foreach ($history as $turn) {
            $role = ($turn['role'] ?? 'user') === 'user' ? 'user' : 'model';
            $text = $turn['content'] ?? '';
            if (!empty($text)) {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]],
                ];
            }
        }

        // Add the current user query
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstructions]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.65,
                'maxOutputTokens' => 1200,
            ],
        ];

        foreach ($candidateModels as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$this->apiKey}";

            try {
                $res = Http::timeout(15)
                    ->withoutVerifying()
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if ($res->successful()) {
                    $data = $res->json();
                    $candidateText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($candidateText) {
                        // Ensure no leftover tips or instructions are included in the final output
                        $candidateText = preg_replace('/(?:\n|>|\s)*💡\s*\*\*Tip:\*\*.*$/msi', '', $candidateText);
                        $candidateText = preg_replace('/(?:\n|>|\s)*💡\s*Tip:.*$/mi', '', $candidateText);
                        return trim($candidateText);
                    }
                }

                Log::warning("Gemini model [{$model}] returned non-success response: " . $res->status() . " - " . substr($res->body(), 0, 150));

                // If quota exhausted (429) or model not found (404), failover to next model in pool!
                if (in_array($res->status(), [429, 404, 503])) {
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini model [{$model}] exception: " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    /**
     * Build live, tenancy-isolated data context for the authenticated user.
     */
    protected function buildLiveUserContext(User $user): array
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $weekEnd = $now->copy()->addDays(7)->endOfDay();

        // 1. User's upcoming meetings within 7 days
        $upcomingMeetings = Meeting::where(function ($q) use ($user) {
            $q->where('organizer_id', $user->id)
                ->orWhereHas('participants', function ($p) use ($user) {
                    $p->where('users.id', $user->id);
                });
        })
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_time', [$todayStart, $weekEnd])
            ->with(['room', 'organizer', 'client'])
            ->orderBy('start_time')
            ->take(8)
            ->get();

        $meetingsSummary = $upcomingMeetings->map(function ($m) use ($user) {
            $isOrganizer = $m->organizer_id === $user->id;
            return [
                'id' => $m->id,
                'title' => $m->title,
                'date' => $m->start_time->format('Y-m-d'),
                'time' => $m->start_time->format('h:i A') . ' - ' . $m->end_time->format('h:i A'),
                'role' => $isOrganizer ? 'Organizer' : 'Participant',
                'room' => $m->room?->name ?? 'Virtual / No Room',
                'client' => $m->client?->name ?? null,
                'url' => route('meetings.show', $m->id),
            ];
        })->toArray();

        // 2. User's open tasks
        $openTasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->orderBy('due_date')
            ->take(6)
            ->get(['id', 'title', 'due_date', 'status', 'priority'])
            ->toArray();

        // 3. Active Rooms in company
        $rooms = Room::where('is_active', true)->get(['id', 'name', 'capacity', 'location'])->toArray();

        // 4. Admin pending approvals count
        $pendingApprovalsCount = 0;
        if ($user->isCompanySuperAdmin() || $user->isDeveloper()) {
            $pendingApprovalsCount = User::withoutGlobalScopes()
                ->where('approval_status', User::STATUS_PENDING)
                ->when(!$user->isDeveloper(), fn ($q) => $q->where('company_id', $user->company_id))
                ->count();
        }

        return [
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'role_label' => $user->role_label,
            'company_name' => $user->company?->name ?? 'Default Organization',
            'department_name' => $user->department?->name ?? 'General',
            'current_time' => $now->format('l, F j, Y • h:i A'),
            'public_booking_link' => !empty($user->username) ? route('booking.show', $user->username) : url('/book/' . ($user->username ?: 'user-' . $user->id)),
            'upcoming_meetings' => $meetingsSummary,
            'open_tasks' => $openTasks,
            'available_rooms' => $rooms,
            'pending_approvals_count' => $pendingApprovalsCount,
        ];
    }

    /**
     * Construct rich system instructions and Schedio app manual for the LLM.
     */
    protected function buildSystemInstructions(User $user, array $ctx): string
    {
        $meetingsJson = json_encode($ctx['upcoming_meetings'], JSON_PRETTY_PRINT);
        $tasksJson = json_encode($ctx['open_tasks'], JSON_PRETTY_PRINT);
        $roomsJson = json_encode($ctx['available_rooms'], JSON_PRETTY_PRINT);

        return <<<PROMPT
You are **Schedio Copilot**, the official intelligent executive AI assistant inside Schedio (Intelligent Enterprise Calendar & Scheduling Suite).
Your primary role is to help the user navigate Schedio, answer any questions about the platform's features, and provide instant insights about their calendar, rooms, tasks, and company workspace.

### Core Rules & Persona:
1. **Language Adaptation**: Always respond in the EXACT same language/dialect the user asks in!
   - If the user writes in **Roman Urdu / Hindi** (e.g. *"meri meetings batao"*, *"naya room kaise add hoga"*, *"revert option kya krta he"*), answer fluently and naturally in Roman Urdu / English.
   - If the user asks in English, answer in polished English.
2. **Helpful & Accurate**: Never guess or hallucinate features that don't exist. Use the exact feature knowledge and live context provided below.
3. **Actionable Links**: Always include markdown links to Schedio pages so users can click and take action immediately (e.g. [Schedule Meeting](/meetings/create), [View Rooms](/rooms), [Pipeline](/clients), [Registrations](/admin/registration-requests)).
4. **Tenant Isolation & Security**: The user belongs to company "{$ctx['company_name']}". Never reveal or discuss other companies' data.
5. **Direct Answers Only**: Answer strictly what the user asks. Never output system configuration tips, API key instructions, or disclaimer notices. Keep responses natural, direct, and focused.

---

### Schedio Platform Feature Guide (Knowledge Base):

1. **Meetings Module** (`/meetings`):
   - Create internal meetings (with company colleagues) or external meetings (with CRM clients).
   - Link: [Nayi Meeting Schedule Karein](/meetings/create)
   - RSVP tracking: Participants can Accept, Decline, or mark Tentative.
   - Rescheduling, cancellation, and recurring frequencies (Daily, Weekly, Monthly).
   - Meeting Workspace (`/meetings/{id}`): Contains collaborative live notes, agendas, and follow-up action items/tasks.

2. **Meeting Suites & Rooms** (`/rooms`):
   - Physical conference rooms and focus pods with capacity limits.
   - Real-time availability checking: System flags room collisions before booking.

3. **Client & Deal Pipeline (CRM)** (`/clients`):
   - Track business development leads through stages: Lead -> Contacted -> Meeting Scheduled -> Proposal Sent -> Won -> Lost.
   - Direct 1-click meeting scheduling from any client card.

4. **Public Self-Booking Links** (`/book/{username}`):
   - Cal.com / Calendly style self-service booking page.
   - Current user's personal booking link: {$ctx['public_booking_link']}
   - External clients can view available conflict-free slots, pick a time, and auto-generate calendar invites and iCal export.

5. **Conflict Detection & Smart Suggestions**:
   - `ConflictDetectionService` automatically checks participant schedules, room occupancy, and company events.
   - Suggests conflict-free gaps to avoid overlapping bookings.

6. **Registration & Approval Flow & Revert Feature** (`/admin/registration-requests`):
   - All new registrations are placed in pending state.
   - **Company Super Admin (CEO)** approval activates the company tenant workspace.
   - **Revert & Free Email Feature**: If an employee accidentally registers as CEO / Company Super Admin instead of staff, or if an application is rejected, admins have a **"Revert & Free Email"** button. This completely purges the mistaken application and frees up their email address so the applicant can immediately re-register cleanly on `/register`!
   - **Re-open Feature**: Restores a rejected application back to pending queue.

7. **Roles & Permissions**:
   - `Developer`: Platform master administrator (sees all companies).
   - `Company Super Admin (CEO)`: Tenant owner, manages staff, departments, approvals, rooms.
   - `Manager`: Department leader, team calendar.
   - `Employee`: Standard calendar, meetings, tasks, notes.
   - `Client`: External guest.

---

### Current Authenticated User Live Workspace Context:
- **User Name**: {$ctx['user_name']}
- **Email**: {$ctx['user_email']}
- **Role**: {$ctx['role_label']} ({$ctx['user_role']})
- **Company Workspace**: {$ctx['company_name']}
- **Department**: {$ctx['department_name']}
- **Current System Time**: {$ctx['current_time']}
- **Personal Public Booking Link**: {$ctx['public_booking_link']}
- **Pending Approvals Waiting (Admin Only)**: {$ctx['pending_approvals_count']}

#### User's Live Upcoming Meetings (Next 7 Days):
{$meetingsJson}

#### User's Pending Action Tasks:
{$tasksJson}

#### Company Available Rooms:
{$roomsJson}

Keep responses structured, concise, and executive-ready with bullet points and bold highlights.
PROMPT;
    }

    /**
     * Local rule-based and live database resolver when Gemini API is unavailable or unconfigured.
     */
    protected function resolveLocalKnowledge(User $user, string $query, array $ctx): string
    {
        $q = strtolower(trim($query));

        // 1. Questions about meetings / schedule / aaj ki meetings
        if (str_contains($q, 'meeting') || str_contains($q, 'schedule') || str_contains($q, 'aaj') || str_contains($q, 'calendar')) {
            $meetings = $ctx['upcoming_meetings'];
            if (empty($meetings)) {
                return "Aapke calendar me agle 7 dino me koi scheduled meeting nahi hai.\n\nAap nayi meeting schedule karne ke liye [Yahan Click Karein](/meetings/create) ya apna [Calendar Hub](/calendar) check karein.";
            }

            $list = "Aapki agli scheduled meetings ye hain:\n\n";
            foreach ($meetings as $m) {
                $list .= "- **{$m['title']}**\n";
                $list .= "  - 📅 **Date & Time:** {$m['date']} ({$m['time']})\n";
                $list .= "  - 🚪 **Room:** {$m['room']}\n";
                $list .= "  - 👤 **Role:** {$m['role']}\n";
                $list .= "  - 🔗 [Meeting Details Dekhein]({$m['url']})\n\n";
            }
            $list .= "Nayi meeting add karne ke liye: [Schedule Meeting](/meetings/create)";
            return $list;
        }

        // 2. Questions about rooms / pods / free room
        if (str_contains($q, 'room') || str_contains($q, 'kamra') || str_contains($q, 'pod') || str_contains($q, 'conference')) {
            $rooms = $ctx['available_rooms'];
            if (empty($rooms)) {
                return "Aapki company me abhi tak koi meeting room configure nahi kiya gaya. [Meeting Rooms Page](/rooms) par ja kar naya room create karein.";
            }

            $list = "Aapki company workspace ke available **Meeting Rooms & Focus Pods**:\n\n";
            foreach ($rooms as $r) {
                $list .= "- **{$r['name']}** (Capacity: {$r['capacity']} people) — Location: {$r['location']}\n";
            }
            $list .= "\nRoom status aur bookings check karne ke liye [Rooms Management Page](/rooms) open karein.";
            return $list;
        }

        // 3. Questions about tasks / action items
        if (str_contains($q, 'task') || str_contains($q, 'kam') || str_contains($q, 'action')) {
            $tasks = $ctx['open_tasks'];
            if (empty($tasks)) {
                return "Zabardast! Aapke paas abhi koi pending task ya action item nahi hai. Tamam tasks complete hain.";
            }

            $list = "Aapke pending tasks aur action items:\n\n";
            foreach ($tasks as $t) {
                $due = !empty($t['due_date']) ? Carbon::parse($t['due_date'])->format('M d, Y') : 'No due date';
                $list .= "- **{$t['title']}** (Priority: " . ucfirst($t['priority'] ?? 'Normal') . ", Due: {$due})\n";
            }
            return $list;
        }

        // 4. Questions about Reverting / Rejected Registrations / CEO mistake
        if (str_contains($q, 'revert') || str_contains($q, 'reject') || str_contains($q, 'ceo') || str_contains($q, 'gmail') || str_contains($q, 'dobara apply')) {
            return "### 🔄 Schedio Revert & Free Email Feature:\n\n"
                . "Agar kisi staff member ne ghalti se **CEO / Company Super Admin** ke liye apply kar diya ho, ya koi registration reject ho gayi ho, to Admin panel me **`Revert & Free Email`** ka option mojood hai:\n\n"
                . "1. [Admin Registration Requests](/admin/registration-requests?status=rejected) par jayein.\n"
                . "2. Rejected card par **Revert & Free Email** button dabayein.\n"
                . "3. Is se applicant ka database record aur pending company purge ho jati hai, aur uska Gmail bilkul free ho jata hai ta ke wo `/register` par ja kar as normal staff dobara apply kar sake.\n"
                . "4. Sath hi **Re-open** ka button bhi hai jis se rejected request dobara Pending queue me chali jati hai agar direct role theek karke approve karna ho.";
        }

        // 5. Questions about Public Booking link / Calendly
        if (str_contains($q, 'public') || str_contains($q, 'book') || str_contains($q, 'link') || str_contains($q, 'share') || str_contains($q, 'calendly')) {
            $link = $ctx['public_booking_link'];
            return "### 🌐 Aapka Personal Public Booking Link:\n\n"
                . "Aap clients ke sath apna personal booking link share kar sakte hain ta ke wo aapke calendar ke free slots dekh kar khud meeting book kar sakein:\n\n"
                . "👉 **Link:** `{$link}`\n\n"
                . "- Jab koi client is link se book karega, to system automatically double-booking check karega aur calendar invite generate karega.";
        }

        // 6. Questions about CRM pipeline / Clients
        if (str_contains($q, 'client') || str_contains($q, 'deal') || str_contains($q, 'pipeline') || str_contains($q, 'crm')) {
            return "### 💼 Schedio Client & Deal Pipeline:\n\n"
                . "Schedio me aap business leads aur deals ko stages me track kar sakte hain:\n"
                . "- **Stages:** Lead → Contacted → Meeting Scheduled → Proposal Sent → Won → Lost\n"
                . "- Har client card se aap 1-click meeting schedule kar sakte hain jo directly aapke calendar se sync ho jati hai.\n\n"
                . "Aap yahan se pipeline manage kar sakte hain: [Client Pipeline Open Karein](/clients)";
        }

        // 7. Questions about conflict detection / smart suggestions
        if (str_contains($q, 'conflict') || str_contains($q, 'smart') || str_contains($q, 'clash') || str_contains($q, 'collision')) {
            return "### ⚡ Smart Conflict Detection Engine:\n\n"
                . "Schedio ka intelligent algorithm har meeting booking par check karta hai:\n"
                . "1. **Room Conflict:** Room pehle se kisi aur meeting ke liye reserved to nahi.\n"
                . "2. **Participant Conflict:** Tamam attendees us waqt kisi doosri meeting ya company event me busy to nahi.\n"
                . "3. **Smart Suggestions:** Agar collision ho, to system automatic alternative conflict-free slots propose karta hai.";
        }

        // Default General Guide
        return "Salam **{$ctx['user_name']}**! Main **Schedio Copilot** hoon.\n\n"
            . "Main aapko Schedio ke tamam features me guide kar sakta hoon aur live information provide kar sakta hoon. Aap mujhse pooch sakte hain:\n\n"
            . "- 📅 *\"Meri aaj ki meetings batao\"*\n"
            . "- 🚪 *\"Kaunse rooms available hain?\"*\n"
            . "- 🎯 *\"Mere pending tasks kya hain?\"*\n"
            . "- 🔄 *\"Rejected application ko revert kaise karein?\"*\n"
            . "- 🌐 *\"Mera public booking link kya hai?\"*\n"
            . "- 💼 *\"Client pipeline kaise use karein?\"*\n\n"
            . "Aap kis cheez ke baray me jan-na chahte hain?";
    }

    /**
     * Provide quick prompt suggestions based on user role and context.
     */
    protected function getDynamicSuggestions(User $user): array
    {
        $base = [
            '📅 Meri upcoming meetings batao',
            '🚪 Free Rooms check karo',
            '🌐 Mera Public Booking link',
            '🎯 Mere pending tasks',
        ];

        if ($user->isCompanySuperAdmin() || $user->isDeveloper()) {
            $base[] = '🔄 Revert & Free Email feature kaise kaam karta hai?';
            $base[] = '👥 Pending approvals kitni hain?';
        } else {
            $base[] = '⚡ Conflict detection kaise kaam karta hai?';
            $base[] = '💼 Client pipeline kaise use karein?';
        }

        return $base;
    }
}
