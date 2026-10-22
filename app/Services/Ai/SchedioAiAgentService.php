<?php

namespace App\Services\Ai;

use App\Models\Client;
use App\Models\Meeting;
use App\Models\MeetingNote;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use App\Services\ConflictDetectionService;
use App\Services\NotificationDispatchService;
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
        // 1. High-Priority Intent & Autonomous Action Execution Check
        $localActionResult = $this->detectAndExecuteLocalIntent($user, $message);
        if ($localActionResult !== null) {
            return [
                'answer' => $localActionResult,
                'suggestions' => $this->getDynamicSuggestions($user),
            ];
        }

        $liveContext = $this->buildLiveUserContext($user);
        $systemInstructions = $this->buildSystemInstructions($user, $liveContext);

        // If Gemini API Key is configured, attempt generative completion
        if (!empty($this->apiKey)) {
            try {
                $response = $this->callGeminiApi($systemInstructions, $message, $history);
                if ($response !== null) {
                    // Check if LLM emitted an [ACTION: ...] tag
                    if (preg_match('/\[ACTION:\s*(\{.*?\})\s*\]/is', $response, $matches)) {
                        $actionPayload = json_decode($matches[1], true);
                        if (is_array($actionPayload)) {
                            $actionResult = $this->executeAgentAction($user, $actionPayload);
                            $cleanResponse = trim(str_replace($matches[0], '', $response));
                            $finalResponse = $actionResult ? ($actionResult . "\n\n" . $cleanResponse) : $cleanResponse;
                            return [
                                'answer' => trim($finalResponse),
                                'suggestions' => $this->getDynamicSuggestions($user),
                            ];
                        }
                    }

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
     * Execute an autonomous agent action directly against the database.
     */
    public function executeAgentAction(User $user, array $action): ?string
    {
        $type = $action['action'] ?? '';

        // 1. Schedule Meeting
        if ($type === 'schedule_meeting') {
            $prompt = $action['prompt'] ?? $action['title'] ?? '';
            $res = $this->quickSchedule($user, $prompt, true);
            if ($res['created'] && !empty($res['meeting'])) {
                $m = $res['meeting'];
                $url = route('meetings.show', $m->id);
                return "✅ **Meeting Successfully Scheduled!**\n\n- 📅 **Title:** {$m->title}\n- ⏰ **Date & Time:** " . $m->start_time->format('l, M d, Y • h:i A') . "\n- 🚪 **Location:** " . ($m->room?->name ?? 'Virtual / No Room') . "\n\n👉 [Meeting Workspace Open Karein]({$url})";
            } elseif ($res['has_conflicts']) {
                $msg = "⚠️ **Schedule Collision Detected!** Requested time par clash hai.\n\n";
                if (!empty($res['alternative_slots'])) {
                    $msg .= "**Alternative Available Slots:**\n";
                    foreach (array_slice($res['alternative_slots'], 0, 3) as $slot) {
                        $msg .= "- ⏰ {$slot['start_formatted']} - {$slot['end_formatted']}\n";
                    }
                }
                return $msg;
            }
        }

        // 2. Create Client (Add New Lead to CRM Pipeline)
        if ($type === 'create_client' || $type === 'add_client') {
            $companyName = trim($action['company_name'] ?? $action['name'] ?? $action['client_name'] ?? '');
            $contactName = trim($action['contact_name'] ?? '');

            if (empty($companyName) && !empty($contactName)) {
                $companyName = $contactName;
            } elseif (!empty($companyName) && empty($contactName)) {
                $contactName = $companyName;
            }

            if (empty($companyName)) {
                $companyName = 'New Lead';
                $contactName = 'New Contact';
            }

            $targetStatus = strtolower(trim($action['status'] ?? 'discovery'));
            $statusMap = [
                'discovery' => Client::STATUS_DISCOVERY,
                'lead' => Client::STATUS_DISCOVERY,
                'scheduled' => Client::STATUS_SCHEDULED,
                'paperwork' => Client::STATUS_PAPERWORK_ONBOARDING,
                'paperwork_onboarding' => Client::STATUS_PAPERWORK_ONBOARDING,
                'onboard' => Client::STATUS_ONBOARDED,
                'onboarded' => Client::STATUS_ONBOARDED,
                'won' => Client::STATUS_ONBOARDED,
                'pause' => Client::STATUS_PAUSE,
                'deadend' => Client::STATUS_DEADEND,
            ];
            $canonicalStatus = $statusMap[$targetStatus] ?? Client::STATUS_DISCOVERY;

            $companyId = $user->company_id ?: (Company::where('status', Company::STATUS_ACTIVE)->value('id') ?: Company::value('id') ?: 1);

            $client = Client::create([
                'company_id' => $companyId,
                'company_name' => $companyName,
                'contact_name' => $contactName,
                'contact_email' => $action['contact_email'] ?? null,
                'contact_phone' => $action['contact_phone'] ?? null,
                'deal_value' => !empty($action['deal_value']) ? (float)$action['deal_value'] : 0,
                'status' => $canonicalStatus,
                'created_by' => $user->id,
                'assigned_to' => $user->id,
                'last_activity_at' => now(),
            ]);

            try {
                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'client_created',
                    'auditable_type' => Client::class,
                    'auditable_id' => $client->id,
                    'description' => "Client '{$client->company_name}' ({$client->contact_name}) added via Schedio Copilot Agent by {$user->name}.",
                    'new_values' => $client->toArray(),
                ]);
            } catch (\Throwable $e) {}

            $url = route('clients.show', $client->id);
            return "✅ **Client Successfully Added to Database!**\n\n**{$client->company_name}** ({$client->contact_name}) ko aapke CRM Pipeline mein **{$client->status_label}** stage par database mein add kar diya gaya hai.\n\n👉 [Client Details Page Open Karein]({$url}) | [CRM Pipeline Dekhein](/clients)";
        }

        // 3. Update Client Status (Onboard, Paperwork, Pause, Discovery, Deadend)
        if ($type === 'update_client' || $type === 'update_client_status') {
            $clientName = trim($action['client_name'] ?? '');
            $targetStatus = strtolower(trim($action['status'] ?? ''));

            $statusMap = [
                'discovery' => Client::STATUS_DISCOVERY,
                'lead' => Client::STATUS_DISCOVERY,
                'scheduled' => Client::STATUS_SCHEDULED,
                'contacted' => Client::STATUS_SCHEDULED,
                'meeting' => Client::STATUS_SCHEDULED,
                'paperwork' => Client::STATUS_PAPERWORK_ONBOARDING,
                'paperwork_onboarding' => Client::STATUS_PAPERWORK_ONBOARDING,
                'proposal' => Client::STATUS_PAPERWORK_ONBOARDING,
                'onboard' => Client::STATUS_ONBOARDED,
                'onboarded' => Client::STATUS_ONBOARDED,
                'won' => Client::STATUS_ONBOARDED,
                'pause' => Client::STATUS_PAUSE,
                'paused' => Client::STATUS_PAUSE,
                'deadend' => Client::STATUS_DEADEND,
                'dead_end' => Client::STATUS_DEADEND,
                'lost' => Client::STATUS_DEADEND,
            ];
            $canonicalStatus = $statusMap[$targetStatus] ?? Client::STATUS_ONBOARDED;

            $clientQuery = Client::withoutGlobalScopes()
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id));

            if (!empty($action['client_id'])) {
                $client = $clientQuery->where('id', (int)$action['client_id'])->first();
            } elseif (!empty($clientName)) {
                // Handle conversational pronouns like "isse", "isko", "ise", "usko", "this client"
                if (preg_match('/^(?:isse|isko|ise|iss\s+ko|use|usko|this\s+client)$/i', $clientName)) {
                    $client = $clientQuery->whereNotIn('status', [Client::STATUS_ONBOARDED, Client::STATUS_DEADEND])
                        ->latest('updated_at')
                        ->first();
                } else {
                    $client = $clientQuery->where(function ($q) use ($clientName) {
                        $q->where('company_name', 'like', "%{$clientName}%")
                          ->orWhere('contact_name', 'like', "%{$clientName}%");
                    })->first();
                }
            } else {
                // If clientName is omitted, pick the most recently active non-onboarded client
                $client = $clientQuery->whereNotIn('status', [Client::STATUS_ONBOARDED, Client::STATUS_DEADEND])
                    ->latest('updated_at')
                    ->first();
            }

            if ($client) {
                $oldLabel = $client->status_label;
                $client->update([
                    'status' => $canonicalStatus,
                    'last_activity_at' => now(),
                ]);
                $url = route('clients.show', $client->id);
                return "✅ **Client Status Updated in Database!**\n\n**{$client->company_name}** ({$client->contact_name}) ko **{$oldLabel}** se shift karke **{$client->status_label}** stage me update kar diya gaya hai.\n\n👉 [Client Details Page Open Karein]({$url}) | [CRM Pipeline Dekhein](/clients)";
            } else {
                // If client doesn't exist and explicit name given, auto-create directly in that stage!
                if (!empty($clientName) && !preg_match('/^(?:isse|isko|ise|iss\s+ko|use|usko|this\s+client)$/i', $clientName)) {
                    $companyId = $user->company_id ?: (Company::where('status', Company::STATUS_ACTIVE)->value('id') ?: Company::value('id') ?: 1);
                    $client = Client::create([
                        'company_id' => $companyId,
                        'company_name' => $clientName,
                        'contact_name' => $clientName,
                        'status' => $canonicalStatus,
                        'created_by' => $user->id,
                        'assigned_to' => $user->id,
                        'last_activity_at' => now(),
                    ]);
                    $url = route('clients.show', $client->id);
                    return "✅ **Client Created & Set to {$client->status_label} in Database!**\n\n**{$client->company_name}** ({$client->contact_name}) ko CRM Pipeline mein naya add karke **{$client->status_label}** stage par set kar diya gaya hai.\n\n👉 [Client Details Page Open Karein]({$url}) | [CRM Pipeline Dekhein](/clients)";
                }

                $recentClients = Client::withoutGlobalScopes()
                    ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                    ->latest('updated_at')
                    ->take(3)
                    ->get();
                $msg = "⚠️ Client **'{$clientName}'** database me nahi mila.\n\nAapki company ke recent leads:\n";
                foreach ($recentClients as $rc) {
                    $msg .= "- **{$rc->company_name}** ({$rc->status_label})\n";
                }
                $firstComp = $recentClients->first()?->company_name ?? 'Client Name';
                $msg .= "\nKripya company name likhein, maslan: *'{$firstComp} ko onboard kar do'*";
                return $msg;
            }
        }

        // 3. Create Task
        if ($type === 'create_task') {
            $title = trim($action['title'] ?? 'Action Item');
            $assignedToName = trim($action['assigned_to'] ?? '');
            $priority = strtolower(trim($action['priority'] ?? 'medium'));
            if (!in_array($priority, ['low', 'medium', 'high', 'urgent'])) {
                $priority = 'medium';
            }
            $dueDate = !empty($action['due_date']) ? Carbon::parse($action['due_date']) : now()->addDays(3);

            $assignee = null;
            if (!empty($assignedToName)) {
                $assignee = User::withoutGlobalScopes()
                    ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                    ->where(function ($q) use ($assignedToName) {
                        $q->where('name', 'like', "%{$assignedToName}%")
                          ->orWhere('email', 'like', "%{$assignedToName}%");
                    })->first();
            }
            if (!$assignee) {
                $assignee = $user;
            }

            $task = Task::create([
                'company_id' => $user->company_id,
                'title' => $title,
                'description' => "Created via Schedio Copilot Agent for {$user->name}",
                'assigned_to' => $assignee->id,
                'created_by' => $user->id,
                'department_id' => $assignee->department_id ?? $user->department_id,
                'status' => 'pending',
                'priority' => $priority,
                'due_date' => $dueDate,
            ]);

            try {
                app(NotificationDispatchService::class)->notifyTaskActivity($task, 'created');
            } catch (\Throwable $e) {}

            return "✅ **Task Created & Assigned in Database!**\n\n- 🎯 **Task:** {$task->title}\n- 👤 **Assigned To:** {$assignee->name}\n- ⚡ **Priority:** " . ucfirst($priority) . "\n- 📅 **Due Date:** " . $dueDate->format('M d, Y') . "\n\n👉 [Tasks Hub Open Karein](/calendar)";
        }

        // 4. Complete Task
        if ($type === 'complete_task') {
            $taskTitle = trim($action['task_title'] ?? $action['title'] ?? '');
            $taskQuery = Task::withoutGlobalScopes()
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                ->where('status', '!=', 'completed');

            if (!empty($taskTitle)) {
                $task = $taskQuery->where('title', 'like', "%{$taskTitle}%")->first();
            } else {
                $task = $taskQuery->where('assigned_to', $user->id)->orderBy('due_date')->first();
            }

            if ($task) {
                $task->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                return "✅ **Task Completed in Database!**\n\nTask **'{$task->title}'** ko successfully **Completed** mark kar diya gaya hai. 🎉";
            } else {
                return "⚠️ Complete karne ke liye koi pending task nahi mila.";
            }
        }

        // 5. Cancel Meeting
        if ($type === 'cancel_meeting') {
            $meetingTitle = trim($action['meeting_title'] ?? $action['title'] ?? '');
            $meetingQuery = Meeting::withoutGlobalScopes()
                ->where(function ($q) use ($user) {
                    $q->where('organizer_id', $user->id)
                      ->orWhereHas('participants', fn ($p) => $p->where('users.id', $user->id));
                })
                ->where('status', '!=', 'cancelled');

            if (!empty($meetingTitle)) {
                $meeting = $meetingQuery->where('title', 'like', "%{$meetingTitle}%")->first();
            } else {
                $meeting = $meetingQuery->where('start_time', '>=', now())->orderBy('start_time')->first();
            }

            if ($meeting) {
                $meeting->update(['status' => 'cancelled']);
                try {
                    app(NotificationDispatchService::class)->notifyMeetingCancelled($meeting);
                } catch (\Throwable $e) {}
                return "✅ **Meeting Cancelled in Database!**\n\nMeeting **'{$meeting->title}'** (" . $meeting->start_time->format('M d, h:i A') . ") cancel kar di gayi hai aur participants ko notification deliver ho gayi hai.";
            } else {
                return "⚠️ Cancel karne ke liye koi active meeting nahi mili.";
            }
        }

        return null;
    }

    /**
     * Detect explicit user intent and execute database mutations immediately.
     */
    protected function detectAndExecuteLocalIntent(User $user, string $query): ?string
    {
        $q = trim($query);

        // 0. Create Client / Add Lead Intent: e.g. "Tonny Brown ko add kar do", "Tonny Brown ko client add kardo", "Add client Tonny Brown", "Add Tonny Brown to pipeline"
        $clientAddMatched = false;
        $newClientName = '';

        if (preg_match('/(?:ek\s+)?(?:naya\s+)?(?:client|lead)?\s*[\'"]?([a-zA-Z0-9\s\.\-_]+?)[\'"]?\s+(?:ko\s+|k\s+)?(?:client|lead|pipeline\s+me|pipeline)?\s*(?:add|create|shamil|dakhil|bana)\s*(?:kar\s*do|kr\s*do|kardo|karo|do)/i', $q, $m)) {
            $newClientName = trim($m[1]);
            $clientAddMatched = true;
        } elseif (preg_match('/^(?:add|create)\s+(?:client|lead)\s+[\'"]?([a-zA-Z0-9\s\.\-_]+?)[\'"]?(?:\s+(?:to|in)\s+pipeline)?$/i', $q, $m)) {
            $newClientName = trim($m[1]);
            $clientAddMatched = true;
        } elseif (preg_match('/^(?:add|create)\s+[\'"]?([a-zA-Z0-9\s\.\-_]+?)[\'"]?\s+(?:to|in)\s+(?:crm|pipeline|leads|clients)$/i', $q, $m)) {
            $newClientName = trim($m[1]);
            $clientAddMatched = true;
        } elseif (preg_match('/^([a-zA-Z0-9\s\.\-_]+?)\s+(?:ko\s+)?(?:client|lead)\s+(?:bana\s*do|banado|add\s*kar\s*do|add\s*krdo|add\s*kardo)/i', $q, $m)) {
            $newClientName = trim($m[1]);
            $clientAddMatched = true;
        }

        if ($clientAddMatched && !empty($newClientName)) {
            $stopWords = ['meeting', 'room', 'task', 'call', 'calendar', 'sync', 'aaj', 'kal'];
            if (!in_array(strtolower($newClientName), $stopWords) && strlen($newClientName) >= 2) {
                $actionResult = $this->executeAgentAction($user, [
                    'action' => 'create_client',
                    'company_name' => $newClientName,
                    'contact_name' => $newClientName,
                    'status' => 'discovery',
                ]);
                if ($actionResult !== null) {
                    return $actionResult;
                }
            }
        }

        // 1. Client Status Update Intent:
        // Pattern A: e.g. "Starlight Media ko onboard kar do", "Acme ko pause kardo", "isse onboard krdo", "isko onboard kar do"
        if (preg_match('/(?:mark\s+)?([a-zA-Z0-9\s\.\-_]+?)\s+(?:ko\s+|k\s+|status\s+to\s+|as\s+)?(onboard|onboarded|pause|paused|deadend|discovery|paperwork|scheduled|won|lost)(?:\s+(?:kar\s*do|kr\s*do|kardo|karo|me\s+shift|shift\s+kar\s*do))?/i', $q, $m)) {
            $clientName = trim($m[1]);
            $status = strtolower(trim($m[2]));

            $stopWords = ['meeting', 'room', 'task', 'call', 'calendar', 'sync', 'aaj', 'kal'];
            if (!in_array(strtolower($clientName), $stopWords) && strlen($clientName) >= 2) {
                $actionResult = $this->executeAgentAction($user, [
                    'action' => 'update_client',
                    'client_name' => $clientName,
                    'status' => $status,
                ]);
                if ($actionResult !== null) {
                    return $actionResult;
                }
            }
        }

        // Pattern B: e.g. "onboard Starlight Media", "pause Acme", "onboard isse"
        if (preg_match('/^(?:onboard|pause)\s+([a-zA-Z0-9\s\.\-_]+?)(?:\s+(?:kar\s*do|kr\s*do|kardo|karo))?$/i', $q, $m)) {
            $status = str_starts_with(strtolower($q), 'pause') ? 'pause' : 'onboarded';
            $clientName = trim($m[1]);
            $actionResult = $this->executeAgentAction($user, [
                'action' => 'update_client',
                'client_name' => $clientName,
                'status' => $status,
            ]);
            if ($actionResult !== null) {
                return $actionResult;
            }
        }

        // 2. Schedule Meeting Intent: e.g. "Sarah ke sath kal 3 baje meeting schedule kar do", "Meeting rakh do kal 4 baje", "Schedule sync tomorrow at 3pm"
        if (
            preg_match('/(?:schedule|rakh\s*do|rakhdo|book|banao|bana\s*do)\s+.*(?:meeting|sync|call|session)/i', $q) ||
            preg_match('/(?:meeting|sync|call|session)\s+(?:rakh\s*do|rakhdo|schedule\s*(?:kar\s*do|kr\s*do|kardo|karo)?|book\s*karo|kr\s*do|krdo|kar\s*do|kardo)/i', $q)
        ) {
            $actionResult = $this->executeAgentAction($user, [
                'action' => 'schedule_meeting',
                'prompt' => $q,
            ]);
            if ($actionResult !== null) {
                return $actionResult;
            }
        }

        // 3. Complete Task Intent: e.g. "Task 'Prepare slides' complete kar do", "Task review contract done"
        $completeMatched = false;
        $completeTitle = '';

        if (preg_match('/(?:task|kam)\s+[\'"]([^\'"]+)[\'"]\s*(?:complete|done|khatam|mukammal)(?:\s+(?:kar\s*do|kr\s*do|kardo|ho\s*gaya))?/i', $q, $m)) {
            $completeTitle = trim($m[1]);
            $completeMatched = true;
        } elseif (preg_match('/(?:mark\s+task|complete\s+task)\s+[\'"]([^\'"]+)[\'"](?:\s+as\s+done|\s+as\s+completed)?/i', $q, $m)) {
            $completeTitle = trim($m[1]);
            $completeMatched = true;
        } elseif (preg_match('/(?:task|kam)\s+(.+?)\s+(?:complete|done|khatam|mukammal)(?:\s+(?:kar\s*do|kr\s*do|kardo|ho\s*gaya))?$/i', $q, $m)) {
            $completeTitle = trim($m[1], " \t\n\r\0\x0B'\"");
            $completeMatched = true;
        }

        if ($completeMatched) {
            $actionResult = $this->executeAgentAction($user, [
                'action' => 'complete_task',
                'task_title' => $completeTitle,
            ]);
            if ($actionResult !== null) {
                return $actionResult;
            }
        }

        // 4. Create Task Intent: e.g. "Ek task bana do 'Deploy v1.2' Sarah ko assign karke", "Task add karo 'Client pitch'"
        $taskMatched = false;
        $taskTitle = '';
        $assignedTo = '';

        if (preg_match('/(?:ek\s+)?(?:task|kam)\s+(?:bana\s*do|banado|create\s*(?:karo|kr\s*do|kar\s*do|kardo)?|add\s*(?:karo|kr\s*do|kar\s*do|kardo)?)\s+[\'"]([^\'"]+)[\'"](?:\s+(?:assigned?\s+to|ko\s+assign|for)\s+([a-zA-Z\s]+))?/i', $q, $m)) {
            $taskTitle = trim($m[1]);
            $assignedTo = isset($m[2]) ? trim($m[2]) : '';
            $taskMatched = true;
        } elseif (preg_match('/(?:create|add)\s+task\s+[\'"]([^\'"]+)[\'"](?:\s+(?:assigned?\s+to|for)\s+([a-zA-Z\s]+))?/i', $q, $m)) {
            $taskTitle = trim($m[1]);
            $assignedTo = isset($m[2]) ? trim($m[2]) : '';
            $taskMatched = true;
        } elseif (preg_match('/(?:ek\s+)?(?:task|kam)\s+(?:bana\s*do|banado|create\s*(?:karo|kr\s*do|kar\s*do|kardo)?|add\s*(?:karo|kr\s*do|kar\s*do|kardo)?)\s+(.+?)(?:\s+(?:assigned?\s+to|ko\s+assign|for)\s+([a-zA-Z\s]+))?$/i', $q, $m)) {
            $taskTitle = trim($m[1], " \t\n\r\0\x0B'\"");
            $assignedTo = isset($m[2]) ? trim($m[2]) : '';
            $taskMatched = true;
        }

        if ($taskMatched && !empty($taskTitle)) {
            $actionResult = $this->executeAgentAction($user, [
                'action' => 'create_task',
                'title' => $taskTitle,
                'assigned_to' => $assignedTo,
            ]);
            if ($actionResult !== null) {
                return $actionResult;
            }
        }

        // 5. Cancel Meeting Intent: e.g. "Meeting 'Design Sync' cancel kar do", "Cancel meeting 'Sprint'"
        $cancelMatched = false;
        $cancelTitle = '';

        if (preg_match('/(?:cancel\s+meeting|meeting\s+cancel)\s+[\'"]([^\'"]+)[\'"](?:\s+(?:kar\s*do|kr\s*do|kardo|karo))?/i', $q, $m)) {
            $cancelTitle = trim($m[1]);
            $cancelMatched = true;
        } elseif (preg_match('/(?:cancel\s+meeting|meeting\s+cancel)\s*(.+?)(?:\s+(?:kar\s*do|kr\s*do|kardo|karo))?$/i', $q, $m)) {
            $cancelTitle = trim($m[1], " \t\n\r\0\x0B'\"");
            $cancelMatched = true;
        }

        if ($cancelMatched) {
            $actionResult = $this->executeAgentAction($user, [
                'action' => 'cancel_meeting',
                'meeting_title' => $cancelTitle,
            ]);
            if ($actionResult !== null) {
                return $actionResult;
            }
        }

        return null;
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

### Autonomous Agent Actions (Live Database Mutations):
You have direct autonomous authorization to execute database actions when the user asks you to perform an operational task!
To execute an action, output a single JSON tag in the exact format: `[ACTION: {"action": "...", ...}]` anywhere in your response.

CRITICAL ANTI-HALLUCINATION RULE:
You CANNOT modify or create database records by simply writing conversational text!
If the user asks you to add, create, onboard, or schedule anything, you MUST output the corresponding [ACTION: ...] tag.
NEVER tell the user "Maine add kar diya hai" or "Lead add ho gaya hai" unless you emit the [ACTION: ...] tag! Without the [ACTION: ...] tag, NO database mutation will occur!

Supported actions:
1. Create Client / Add Lead:
   [ACTION: {"action": "create_client", "company_name": "<Client or Company Name>", "contact_name": "<Contact Person>", "status": "discovery"}]
2. Update Client Status (Onboarding / Pipeline Shift):
   [ACTION: {"action": "update_client", "client_name": "<Client or Company Name>", "status": "<onboarded|paperwork|scheduled|pause|deadend|discovery>"}]
3. Schedule Meeting:
   [ACTION: {"action": "schedule_meeting", "prompt": "<natural language text, e.g. 'Sync with Sarah tomorrow at 3pm'>"}]
4. Create Task:
   [ACTION: {"action": "create_task", "title": "<Task Title>", "assigned_to": "<Assignee Name>", "priority": "<low|medium|high|urgent>", "due_date": "YYYY-MM-DD"}]
5. Complete Task:
   [ACTION: {"action": "complete_task", "task_title": "<Task Title>"}]
6. Cancel Meeting:
   [ACTION: {"action": "cancel_meeting", "meeting_title": "<Meeting Title>"}]

Always confirm the action to the user in friendly, conversational language (Roman Urdu or English matching the user's query).

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
            '⚡ Starlight Media ko onboard kar do',
            '📅 Kal 3 baje meeting schedule kar do',
            '🎯 Ek task bana do Sarah ko assign karke',
            '📅 Meri upcoming meetings batao',
            '🚪 Free Rooms check karo',
            '🌐 Mera Public Booking link',
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

    /**
     * Call Google Gemini API expecting strict structured JSON response.
     */
    public function callGeminiJsonApi(string $systemInstructions, string $prompt, array $defaultFallback = []): array
    {
        if (empty($this->apiKey)) {
            return $defaultFallback;
        }

        $candidateModels = array_values(array_unique(array_filter([
            'gemini-3.5-flash-lite',
            'gemini-flash-lite-latest',
            $this->model,
            'gemini-3.8-flash',
            'gemini-flash-latest',
        ])));

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstructions . "\nIMPORTANT: You MUST respond ONLY with a valid JSON object matching the requested schema. No markdown formatting, no code backticks, no extra text."]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 2048,
                'responseMimeType' => 'application/json',
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
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text) {
                        $cleaned = trim($text);
                        if (str_starts_with($cleaned, '```json')) {
                            $cleaned = substr($cleaned, 7);
                        } elseif (str_starts_with($cleaned, '```')) {
                            $cleaned = substr($cleaned, 3);
                        }
                        if (str_ends_with($cleaned, '```')) {
                            $cleaned = substr($cleaned, 0, -3);
                        }
                        $decoded = json_decode(trim($cleaned), true);
                        if (is_array($decoded)) {
                            return $decoded;
                        }
                    }
                }

                if (in_array($res->status(), [429, 404, 503])) {
                    continue;
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini JSON call failed with model [{$model}]: " . $e->getMessage());
                continue;
            }
        }

        return $defaultFallback;
    }

    /**
     * Natural Language AI Quick-Scheduler ("Prompt-to-Meeting").
     *
     * Parses free-form text into meeting parameters, validates conflicts,
     * suggests alternative slots if collisions occur, and optionally books the meeting.
     */
    public function quickSchedule(User $user, string $prompt, bool $autoCreate = false): array
    {
        $now = Carbon::now();
        $companyId = $user->company_id;

        // Fetch company context for entity resolution
        $companyUsers = User::withoutGlobalScopes()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('approval_status', User::STATUS_APPROVED)
            ->get(['id', 'name', 'email']);

        $rooms = Room::withoutGlobalScopes()
            ->where('is_active', true)
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get(['id', 'name', 'capacity', 'location']);

        $clients = Client::withoutGlobalScopes()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get(['id', 'company_name', 'contact_name', 'contact_email']);

        $usersContext = $companyUsers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values()->toArray();
        $roomsContext = $rooms->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'capacity' => $r->capacity])->values()->toArray();
        $clientsContext = $clients->map(fn ($c) => ['id' => $c->id, 'company_name' => $c->company_name, 'contact_name' => $c->contact_name])->values()->toArray();

        $systemInstructions = <<<SYS
You are Schedio's intelligent Natural Language Scheduling Engine.
Your job is to parse the user's meeting request prompt into structured JSON.
Current System Date & Time: {$now->toIso8601String()} ({$now->format('l, F j, Y h:i A')}).
User Timezone: {$user->timezone}.
Current User: {$user->name} (ID: {$user->id}).

Available Company Users (match attendee names to these IDs):
SYS . json_encode($usersContext, JSON_PRETTY_PRINT) . <<<SYS

Available Meeting Rooms (match requested room to these IDs):
SYS . json_encode($roomsContext, JSON_PRETTY_PRINT) . <<<SYS

Available CRM Clients (match client to these IDs if external meeting):
SYS . json_encode($clientsContext, JSON_PRETTY_PRINT) . <<<SYS

Output Schema in strict JSON:
{
  "title": "Clear meeting title (string)",
  "description": "Optional brief description (string or null)",
  "start_time": "YYYY-MM-DD HH:MM:SS",
  "end_time": "YYYY-MM-DD HH:MM:SS",
  "duration_minutes": 30,
  "room_id": 1,
  "room_name": "Room Name or null",
  "participant_ids": [1, 2],
  "participant_names": ["Name 1", "Name 2"],
  "client_id": null,
  "client_name": null,
  "location": "Room Name or Virtual",
  "is_virtual": false,
  "summary_sentence": "Scheduling a 30-minute sync with ... on ... at ..."
}

Rules:
1. Understand relative dates: "tomorrow", "next Tuesday", "this Friday at 3pm", "in 2 hours". Calculate the exact YYYY-MM-DD HH:MM:SS based on Current System Date & Time.
2. If start time hour is given without duration, default duration_minutes to 30 or 45 minutes.
3. If no specific time of day is mentioned, default to 10:00 AM on the target date.
4. If attendee names or rooms match available options, select their IDs.
SYS;

        // Fallback parser if Gemini is unconfigured or fails
        $defaultFallback = $this->heuristicParseMeetingPrompt($prompt, $user, $companyUsers, $rooms, $clients, $now);

        $parsed = $this->callGeminiJsonApi($systemInstructions, $prompt, $defaultFallback);

        // Sanitize and validate parsed timestamps
        try {
            $startTime = !empty($parsed['start_time']) ? Carbon::parse($parsed['start_time']) : $now->copy()->addDay()->setTime(10, 0, 0);
            $durationMinutes = (int)($parsed['duration_minutes'] ?? 30);
            if ($durationMinutes < 10) {
                $durationMinutes = 30;
            }
            $endTime = !empty($parsed['end_time']) ? Carbon::parse($parsed['end_time']) : $startTime->copy()->addMinutes($durationMinutes);
            if ($endTime->lte($startTime)) {
                $endTime = $startTime->copy()->addMinutes($durationMinutes);
            }
        } catch (\Throwable $e) {
            $startTime = $now->copy()->addDay()->setTime(10, 0, 0);
            $endTime = $startTime->copy()->addMinutes(30);
            $durationMinutes = 30;
        }

        $parsed['start_time'] = $startTime->format('Y-m-d H:i:s');
        $parsed['end_time'] = $endTime->format('Y-m-d H:i:s');
        $parsed['duration_minutes'] = $durationMinutes;

        // Ensure only valid company users exist in participant_ids
        $validUserIds = $companyUsers->pluck('id')->all();
        $participantIds = array_values(array_unique(array_filter(array_map('intval', (array)($parsed['participant_ids'] ?? [])))));
        $participantIds = array_values(array_intersect($participantIds, $validUserIds));

        $roomId = !empty($parsed['room_id']) ? (int)$parsed['room_id'] : null;
        if ($roomId && !$rooms->contains('id', $roomId)) {
            $roomId = null;
        }

        // Verify conflicts using ConflictDetectionService
        /** @var ConflictDetectionService $conflictService */
        $conflictService = app(ConflictDetectionService::class);
        $availability = $conflictService->checkAvailability($startTime, $endTime, $roomId, $participantIds);

        $hasConflicts = $availability['has_conflicts'] ?? false;
        $alternativeSlots = [];

        if ($hasConflicts) {
            $alternativeSlots = $conflictService->suggestAvailableSlots($startTime, $durationMinutes, $roomId, $participantIds);
        }

        // Auto-create meeting if requested and no conflicts exist
        $createdMeeting = null;
        if ($autoCreate && !$hasConflicts) {
            $clientId = !empty($parsed['client_id']) ? (int)$parsed['client_id'] : null;
            if ($clientId && !$clients->contains('id', $clientId)) {
                $clientId = null;
            }

            $createdMeeting = Meeting::create([
                'company_id' => $companyId,
                'title' => $parsed['title'] ?? 'Scheduled Meeting',
                'description' => $parsed['description'] ?? "Created via Schedio AI Copilot: \"{$prompt}\"",
                'organizer_id' => $user->id,
                'department_id' => $user->department_id,
                'room_id' => $roomId,
                'client_id' => $clientId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $user->timezone ?? config('app.timezone', 'UTC'),
                'status' => 'scheduled',
                'type' => $clientId ? 'client' : 'internal',
                'location' => $parsed['location'] ?? ($roomId ? $rooms->firstWhere('id', $roomId)?->name : 'Virtual Meeting'),
                'metadata' => [
                    'created_via' => 'ai_quick_schedule',
                    'original_prompt' => $prompt,
                ],
            ]);

            // Attach organizer
            $createdMeeting->participants()->attach($user->id, ['status' => 'accepted', 'role' => 'organizer']);

            // Attach invited participants
            foreach ($participantIds as $pId) {
                if ($pId !== $user->id) {
                    $createdMeeting->participants()->attach($pId, ['status' => 'pending', 'role' => 'participant']);
                }
            }

            // Dispatch notification invites
            try {
                /** @var NotificationDispatchService $notifier */
                $notifier = app(NotificationDispatchService::class);
                $notifier->notifyMeetingInvitation($createdMeeting);
            } catch (\Throwable $e) {
                Log::warning('Failed to dispatch notifications for AI scheduled meeting: ' . $e->getMessage());
            }

            $createdMeeting->load(['room', 'organizer', 'participants', 'client']);
        }

        return [
            'status' => $hasConflicts ? 'conflict_detected' : ($createdMeeting ? 'created' : 'ready_to_book'),
            'created' => (bool)$createdMeeting,
            'has_conflicts' => $hasConflicts,
            'summary' => $parsed['summary_sentence'] ?? "Meeting: {$parsed['title']} on " . $startTime->format('M d, Y h:i A'),
            'parsed' => $parsed,
            'conflict_details' => $hasConflicts ? $availability : null,
            'alternative_slots' => $alternativeSlots,
            'meeting' => $createdMeeting,
        ];
    }

    /**
     * Heuristic parser when LLM is unavailable.
     */
    protected function heuristicParseMeetingPrompt(
        string $prompt,
        User $user,
        $companyUsers,
        $rooms,
        $clients,
        Carbon $now
    ): array {
        $p = strtolower($prompt);

        // Duration detection
        $duration = 30;
        if (preg_match('/(\d+)\s*(?:min|minute)/i', $p, $m)) {
            $duration = (int)$m[1];
        } elseif (preg_match('/(\d+)\s*(?:hr|hour)/i', $p, $m)) {
            $duration = (int)$m[1] * 60;
        }

        // Date detection
        $targetDate = $now->copy();
        if (str_contains($p, 'tomorrow') || str_contains($p, 'kal')) {
            $targetDate->addDay();
        } elseif (str_contains($p, 'next week')) {
            $targetDate->addWeek();
        } else {
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                if (str_contains($p, $day)) {
                    $targetDate->next($day);
                    break;
                }
            }
        }

        // Time detection
        $hour = 10;
        $minute = 0;
        if (preg_match('/(?:at|after|ko)?\s*(\d{1,2})(?::(\d{2}))?\s*(am|pm)/i', $prompt, $tm)) {
            $h = (int)$tm[1];
            $min = isset($tm[2]) && $tm[2] !== '' ? (int)$tm[2] : 0;
            $meridiem = strtolower($tm[3]);
            if ($meridiem === 'pm' && $h < 12) {
                $h += 12;
            } elseif ($meridiem === 'am' && $h === 12) {
                $h = 0;
            }
            $hour = $h;
            $minute = $min;
        } elseif (str_contains($p, 'afternoon') || str_contains($p, 'dopahar')) {
            $hour = 14;
        } elseif (str_contains($p, 'evening') || str_contains($p, 'sham')) {
            $hour = 17;
        }

        $targetDate->setTime($hour, $minute, 0);
        if ($targetDate->lt($now)) {
            $targetDate->addDay();
        }

        // Room matching
        $matchedRoom = null;
        foreach ($rooms as $r) {
            if (stripos($prompt, $r->name) !== false) {
                $matchedRoom = $r;
                break;
            }
        }

        // User matching
        $matchedUsers = [];
        foreach ($companyUsers as $u) {
            if ($u->id === $user->id) continue;
            $parts = explode(' ', $u->name);
            $firstName = $parts[0] ?? '';
            if (stripos($prompt, $u->name) !== false || (!empty($firstName) && strlen($firstName) >= 3 && stripos($prompt, $firstName) !== false)) {
                $matchedUsers[] = $u;
            }
        }

        // Client matching
        $matchedClient = null;
        foreach ($clients as $c) {
            if (stripos($prompt, $c->company_name) !== false || stripos($prompt, $c->contact_name) !== false) {
                $matchedClient = $c;
                break;
            }
        }

        $title = 'Team Sync';
        if ($matchedClient) {
            $title = 'Meeting with ' . $matchedClient->company_name;
        } elseif (!empty($matchedUsers)) {
            $title = 'Sync with ' . implode(' & ', array_map(fn($u) => $u->name, $matchedUsers));
        }

        $endTime = $targetDate->copy()->addMinutes($duration);

        return [
            'title' => $title,
            'description' => "Scheduled from request: {$prompt}",
            'start_time' => $targetDate->format('Y-m-d H:i:s'),
            'end_time' => $endTime->format('Y-m-d H:i:s'),
            'duration_minutes' => $duration,
            'room_id' => $matchedRoom?->id,
            'room_name' => $matchedRoom?->name,
            'participant_ids' => array_map(fn($u) => $u->id, $matchedUsers),
            'participant_names' => array_map(fn($u) => $u->name, $matchedUsers),
            'client_id' => $matchedClient?->id,
            'client_name' => $matchedClient?->company_name,
            'location' => $matchedRoom?->name ?? 'Virtual Room',
            'is_virtual' => $matchedRoom === null,
            'summary_sentence' => "Scheduling {$title} on " . $targetDate->format('M d, Y h:i A') . ($matchedRoom ? " in {$matchedRoom->name}" : " (Virtual)"),
        ];
    }

    /**
     * AI Meeting Notes, Summary & Auto-Task Extractor (Granola / Otter style).
     */
    public function summarizeMeetingNotes(
        Meeting $meeting,
        ?string $rawNotes = null,
        bool $autoCreateTasks = false,
        bool $saveToMeetingNotes = true,
        ?User $author = null
    ): array {
        $meeting->loadMissing(['organizer', 'participants', 'room', 'client', 'notes']);

        $organizerName = $meeting->organizer?->name ?? 'Organizer';
        $participants = $meeting->participants->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'email' => $p->email])->toArray();
        if ($meeting->organizer && !collect($participants)->contains('id', $meeting->organizer->id)) {
            $participants[] = ['id' => $meeting->organizer->id, 'name' => $meeting->organizer->name, 'email' => $meeting->organizer->email];
        }

        // Build existing notes text if raw notes not provided
        $notesText = $rawNotes;
        if (empty($notesText)) {
            $notesText = $meeting->notes->map(function ($n) {
                return "Agenda: {$n->agenda}\nDiscussion: {$n->discussion}\nDecisions: {$n->decisions}\nAction Items: {$n->action_items}";
            })->filter()->implode("\n---\n");
        }

        if (empty($notesText)) {
            $notesText = "Meeting Title: {$meeting->title}\nDescription: {$meeting->description}\nNo detailed discussion recorded.";
        }

        $systemInstructions = <<<SYS
You are Schedio's Executive AI Meeting Assistant (Granola / Otter AI style).
Synthesize the provided meeting transcript or raw notes into high-level executive minutes.
Meeting Information:
- Title: {$meeting->title}
- Scheduled: {$meeting->start_time?->format('l, F j, Y h:i A')}
- Room / Location: {$meeting->location}
- Client: {$meeting->client?->company_name}
- Participants:
SYS . json_encode($participants, JSON_PRETTY_PRINT) . <<<SYS

Generate structured JSON with the following exact schema:
{
  "executive_summary": "Crisp 2-3 paragraph professional summary of the meeting, context, and discussions.",
  "key_decisions": [
    "Decision 1 reached by the team",
    "Decision 2..."
  ],
  "action_items": [
    {
      "title": "Clear action-oriented task description",
      "assigned_to_user_id": 1,
      "assigned_to_name": "Full Name of Assignee",
      "priority": "high",
      "due_date": "YYYY-MM-DD"
    }
  ],
  "follow_up_recommended": "YYYY-MM-DD or null"
}

Rules:
1. Match action items to the closest participant name and assigned_to_user_id from the participants list above.
2. Priority must be one of: "low", "medium", "high", "urgent".
3. Due date should be a realistic future date (e.g. 3 to 7 days from meeting date).
SYS;

        $defaultFallback = [
            'executive_summary' => "Meeting '{$meeting->title}' was conducted with {$organizerName} and team. Core objectives and next development milestones were discussed.",
            'key_decisions' => [
                "Agreed on milestone objectives for '{$meeting->title}'.",
                "Assigned follow-up deliverables to designated team members.",
            ],
            'action_items' => [
                [
                    'title' => "Follow up on deliverables from '{$meeting->title}'",
                    'assigned_to_user_id' => $meeting->organizer_id,
                    'assigned_to_name' => $organizerName,
                    'priority' => 'high',
                    'due_date' => Carbon::now()->addDays(3)->format('Y-m-d'),
                ],
            ],
            'follow_up_recommended' => Carbon::now()->addDays(7)->format('Y-m-d'),
        ];

        $summary = $this->callGeminiJsonApi($systemInstructions, $notesText, $defaultFallback);

        // Save generated note to meeting_notes if requested
        $createdNote = null;
        if ($saveToMeetingNotes) {
            $authorId = $author?->id ?? $meeting->organizer_id;
            $decisionsText = is_array($summary['key_decisions'] ?? null)
                ? implode("\n", array_map(fn($d) => "• " . $d, $summary['key_decisions']))
                : ($summary['key_decisions'] ?? '');

            $actionsText = is_array($summary['action_items'] ?? null)
                ? implode("\n", array_map(fn($a) => "• [ ] " . ($a['title'] ?? '') . (!empty($a['assigned_to_name']) ? " (@{$a['assigned_to_name']})" : "") . (!empty($a['due_date']) ? " [Due: {$a['due_date']}]" : ""), $summary['action_items']))
                : '';

            $createdNote = MeetingNote::create([
                'company_id' => $meeting->company_id,
                'meeting_id' => $meeting->id,
                'user_id' => $authorId,
                'agenda' => "AI Minutes: {$meeting->title}",
                'discussion' => $summary['executive_summary'] ?? 'Meeting discussion notes.',
                'decisions' => $decisionsText,
                'action_items' => $actionsText,
                'follow_up_date' => !empty($summary['follow_up_recommended']) ? Carbon::parse($summary['follow_up_recommended']) : null,
            ]);
        }

        // Auto-create tasks in database if requested
        $createdTasks = [];
        if ($autoCreateTasks && !empty($summary['action_items']) && is_array($summary['action_items'])) {
            /** @var NotificationDispatchService $notifier */
            $notifier = app(NotificationDispatchService::class);

            foreach ($summary['action_items'] as $item) {
                if (empty($item['title'])) continue;

                $assignedTo = !empty($item['assigned_to_user_id']) ? (int)$item['assigned_to_user_id'] : $meeting->organizer_id;
                // Verify assignee belongs to company
                if (!collect($participants)->contains('id', $assignedTo)) {
                    $assignedTo = $meeting->organizer_id;
                }

                $priority = in_array($item['priority'] ?? '', ['low', 'medium', 'high', 'urgent']) ? $item['priority'] : 'medium';
                $dueDate = !empty($item['due_date']) ? Carbon::parse($item['due_date']) : Carbon::now()->addDays(3);

                $task = Task::create([
                    'company_id' => $meeting->company_id,
                    'meeting_id' => $meeting->id,
                    'title' => $item['title'],
                    'description' => "Extracted by Schedio AI Copilot from meeting '{$meeting->title}'",
                    'assigned_to' => $assignedTo,
                    'created_by' => $author?->id ?? $meeting->organizer_id,
                    'department_id' => $meeting->department_id,
                    'status' => 'pending',
                    'priority' => $priority,
                    'due_date' => $dueDate,
                ]);

                try {
                    $notifier->notifyTaskActivity($task, 'created');
                } catch (\Throwable $e) {
                    Log::warning("Failed to dispatch task activity notification for task {$task->id}: " . $e->getMessage());
                }

                $createdTasks[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'assigned_to' => $task->assigned_to,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date?->format('Y-m-d'),
                ];
            }
        }

        return [
            'meeting_id' => $meeting->id,
            'meeting_title' => $meeting->title,
            'executive_summary' => $summary['executive_summary'] ?? '',
            'key_decisions' => $summary['key_decisions'] ?? [],
            'action_items' => $summary['action_items'] ?? [],
            'follow_up_recommended' => $summary['follow_up_recommended'] ?? null,
            'saved_note_id' => $createdNote?->id,
            'created_tasks_count' => count($createdTasks),
            'created_tasks' => $createdTasks,
        ];
    }

    /**
     * Smart AI Daily Executive Briefing ("Morning Digest").
     */
    public function getDailyBriefing(User $user, ?Carbon $date = null): array
    {
        $targetDate = $date ? $date->copy() : Carbon::today();
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();

        // 1. Fetch user's meetings for the day
        $meetings = Meeting::where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                  ->orWhereHas('participants', fn ($p) => $p->where('users.id', $user->id));
            })
            ->where('status', '!=', 'cancelled')
            ->whereBetween('start_time', [$startOfDay, $endOfDay])
            ->with(['room', 'organizer', 'client', 'participants'])
            ->orderBy('start_time')
            ->get();

        // 2. Fetch pending tasks due on or before target date
        $tasks = Task::where('assigned_to', $user->id)
            ->where('status', '!=', 'completed')
            ->where(function ($q) use ($endOfDay) {
                $q->whereNull('due_date')
                  ->orWhere('due_date', '<=', $endOfDay);
            })
            ->orderByRaw("CASE WHEN priority = 'urgent' THEN 1 WHEN priority = 'high' THEN 2 WHEN priority = 'medium' THEN 3 ELSE 4 END")
            ->orderBy('due_date')
            ->take(8)
            ->get();

        // 3. Compute Deep Work Focus Blocks between 9 AM and 6 PM
        $workdayStart = $targetDate->copy()->setTime(9, 0, 0);
        $workdayEnd = $targetDate->copy()->setTime(18, 0, 0);
        $focusBlocks = [];

        $sortedMeetings = $meetings->sortBy('start_time')->values();
        $cursor = $workdayStart->copy();

        foreach ($sortedMeetings as $m) {
            $mStart = $m->start_time;
            $mEnd = $m->end_time;

            if ($mStart->gt($cursor)) {
                $gapMinutes = $cursor->diffInMinutes($mStart);
                if ($gapMinutes >= 60) {
                    $focusBlocks[] = [
                        'start' => $cursor->format('h:i A'),
                        'end' => $mStart->format('h:i A'),
                        'duration_minutes' => $gapMinutes,
                        'label' => "{$cursor->format('h:i A')} - {$mStart->format('h:i A')} ({$gapMinutes} mins uninterrupted focus)",
                    ];
                }
            }

            if ($mEnd->gt($cursor)) {
                $cursor = $mEnd->copy();
            }
        }

        if ($cursor->lt($workdayEnd)) {
            $gapMinutes = $cursor->diffInMinutes($workdayEnd);
            if ($gapMinutes >= 60) {
                $focusBlocks[] = [
                    'start' => $cursor->format('h:i A'),
                    'end' => $workdayEnd->format('h:i A'),
                    'duration_minutes' => $gapMinutes,
                    'label' => "{$cursor->format('h:i A')} - {$workdayEnd->format('h:i A')} ({$gapMinutes} mins uninterrupted focus)",
                ];
            }
        }

        $meetingsSummary = $meetings->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'time' => $m->start_time->format('h:i A') . ' - ' . $m->end_time->format('h:i A'),
            'room' => $m->room?->name ?? ($m->meeting_link ? 'Virtual Meeting' : 'No Room Assigned'),
            'client' => $m->client?->company_name,
            'participants_count' => $m->participants->count(),
        ])->toArray();

        $tasksSummary = $tasks->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'priority' => $t->priority,
            'due_date' => $t->due_date?->format('Y-m-d') ?? 'No date',
        ])->toArray();

        $greetingHour = (int)Carbon::now()->format('H');
        $greetingPrefix = $greetingHour < 12 ? 'Good morning' : ($greetingHour < 17 ? 'Good afternoon' : 'Good evening');
        $greeting = "{$greetingPrefix}, {$user->name}!";

        $headline = count($meetings) > 0
            ? "You have " . count($meetings) . " " . (count($meetings) === 1 ? 'meeting' : 'meetings') . " and " . count($tasks) . " pending " . (count($tasks) === 1 ? 'task' : 'tasks') . " scheduled for " . $targetDate->format('l, F j') . "."
            : "Your calendar is clear of meetings for " . $targetDate->format('l, F j') . ", with " . count($tasks) . " action items to tackle.";

        $systemInstructions = <<<SYS
You are Schedio Copilot creating an executive morning daily digest.
Generate a concise, motivating executive summary and 1 high-value productivity tip.
User: {$user->name}
Target Date: {$targetDate->format('l, F j, Y')}
Meetings:
SYS . json_encode($meetingsSummary, JSON_PRETTY_PRINT) . <<<SYS
Tasks:
SYS . json_encode($tasksSummary, JSON_PRETTY_PRINT) . <<<SYS
Focus Blocks:
SYS . json_encode($focusBlocks, JSON_PRETTY_PRINT) . <<<SYS

Output Schema in strict JSON:
{
  "summary": "2-3 crisp sentences highlighting what matters most today, key meetings, and recommended game plan.",
  "ai_productivity_tip": "One actionable high-leverage piece of advice tailored to today's schedule and focus blocks."
}
SYS;

        $defaultFallback = [
            'summary' => count($meetings) > 0
                ? "You have a productive day ahead with " . count($meetings) . " meetings scheduled. Focus on your highest priority deliverables during your available focus windows."
                : "You have a clear calendar today, making it an ideal day to complete your pending tasks and advance key projects.",
            'ai_productivity_tip' => !empty($focusBlocks)
                ? "Leverage your " . $focusBlocks[0]['label'] . " for high-concentration deep work."
                : "Block out 30 minutes in the afternoon to review incoming deliverables and plan for tomorrow.",
        ];

        $aiRes = $this->callGeminiJsonApi($systemInstructions, "Generate daily briefing for {$user->name}", $defaultFallback);

        return [
            'date' => $targetDate->format('Y-m-d'),
            'date_formatted' => $targetDate->format('l, F j, Y'),
            'greeting' => $greeting,
            'headline' => $headline,
            'summary' => $aiRes['summary'] ?? $defaultFallback['summary'],
            'ai_productivity_tip' => $aiRes['ai_productivity_tip'] ?? $defaultFallback['ai_productivity_tip'],
            'meetings_count' => count($meetings),
            'tasks_count' => count($tasks),
            'focus_blocks' => $focusBlocks,
            'meetings' => $meetingsSummary,
            'tasks' => $tasksSummary,
        ];
    }

    /**
     * AI Client Deal Intelligence (CRM / BD Copilot).
     */
    public function getClientDealInsights(Client $client, User $user): array
    {
        // Enforce tenancy
        if ($user->company_id && $client->company_id !== $user->company_id && !$user->isDeveloper()) {
            abort(403, 'Unauthorized access to client records.');
        }

        $client->loadMissing(['meetings' => fn ($q) => $q->orderBy('start_time', 'desc'), 'assignedTo']);

        $lastMeeting = $client->meetings->first();
        $lastActivity = $client->last_activity_at ?: ($lastMeeting?->start_time ?: $client->created_at);
        $daysInactive = $lastActivity ? (int)$lastActivity->diffInDays(now()) : 0;

        // Deal health score algorithm
        $score = 100;
        $riskFactors = [];

        if ($client->status === Client::STATUS_DEADEND) {
            $score = 15;
            $riskFactors[] = "Deal marked as dead-end / lost.";
        } elseif ($client->status === Client::STATUS_PAUSE) {
            $score = 45;
            $riskFactors[] = "Deal is currently paused.";
        } elseif ($client->status === Client::STATUS_ONBOARDED) {
            $score = 98;
        } else {
            if ($daysInactive >= 14) {
                $score -= 35;
                $riskFactors[] = "No recorded activity or meeting for {$daysInactive} days.";
            } elseif ($daysInactive >= 7) {
                $score -= 20;
                $riskFactors[] = "Over a week since last interaction ({$daysInactive} days).";
            } elseif ($daysInactive >= 4) {
                $score -= 10;
            }

            if ($client->status === Client::STATUS_PAPERWORK_ONBOARDING && $daysInactive >= 5) {
                $score -= 20;
                $riskFactors[] = "Paperwork/Onboarding stage has stalled without contract follow-up.";
            }

            if ($client->status === Client::STATUS_DISCOVERY && $client->meetings->isEmpty()) {
                $score -= 15;
                $riskFactors[] = "No discovery meeting scheduled yet.";
            }
        }

        $score = max(10, min(100, $score));

        $healthStatus = match (true) {
            $score >= 75 => 'healthy',
            $score >= 50 => 'attention',
            default => 'at_risk',
        };

        $defaultNextAction = match ($client->status) {
            Client::STATUS_DISCOVERY => "Schedule initial discovery call to qualify project requirements and budget.",
            Client::STATUS_SCHEDULED => "Review client brief and send confirmation reminder ahead of upcoming demo.",
            Client::STATUS_PAPERWORK_ONBOARDING => "Send polite contract follow-up regarding SLA review and digital signature.",
            Client::STATUS_PAUSE => "Check in with client regarding timeline resumption and new quarterly updates.",
            Client::STATUS_DEADEND => "Conduct win/loss retrospective and set automated 6-month reactivation ping.",
            Client::STATUS_ONBOARDED => "Conduct onboarding kickoff meeting and assign dedicated customer success manager.",
            default => "Schedule a check-in meeting to keep momentum active.",
        };

        $systemInstructions = <<<SYS
You are Schedio's AI Business Development & Deal Copilot.
Analyze this CRM deal card and formulate the optimal next best action and a customized, ready-to-send follow-up email.
Deal Details:
- Company Name: {$client->company_name}
- Contact Person: {$client->contact_name} ({$client->contact_role})
- Contact Email: {$client->contact_email}
- Pipeline Stage: {$client->status_label} ({$client->status})
- Deal Value: \${$client->deal_value}
- Days Since Last Contact: {$daysInactive}
- Notes: {$client->notes}

Output Schema in strict JSON:
{
  "next_best_action": "Specific, actionable step the BD rep should take right now.",
  "follow_up_subject": "Professional email subject line",
  "follow_up_body": "Polite, personalized 2-paragraph email draft addressed to the contact person, signed by the account team."
}
SYS;

        $defaultFallback = [
            'next_best_action' => $defaultNextAction,
            'follow_up_subject' => "Following up: Schedio & {$client->company_name} collaboration",
            'follow_up_body' => "Hi {$client->contact_name},\n\nI hope you are having a productive week. I am following up on our recent conversation regarding our collaboration with {$client->company_name}.\n\nPlease let us know if you have any questions or if you'd like to reserve a quick 15-minute slot on our calendar this week to align on next steps.\n\nBest regards,\n{$user->name}\nSchedio Team",
        ];

        $aiRes = $this->callGeminiJsonApi($systemInstructions, "Generate deal insights and follow-up draft for {$client->company_name}", $defaultFallback);

        return [
            'client_id' => $client->id,
            'company_name' => $client->company_name,
            'contact_name' => $client->contact_name,
            'contact_email' => $client->contact_email,
            'stage' => $client->status,
            'stage_label' => $client->status_label,
            'deal_value' => $client->deal_value,
            'health_score' => $score,
            'health_status' => $healthStatus,
            'days_since_last_contact' => $daysInactive,
            'risk_factors' => $riskFactors,
            'next_best_action' => $aiRes['next_best_action'] ?? $defaultNextAction,
            'follow_up_draft' => [
                'subject' => $aiRes['follow_up_subject'] ?? $defaultFallback['follow_up_subject'],
                'body' => $aiRes['follow_up_body'] ?? $defaultFallback['follow_up_body'],
            ],
        ];
    }
}
