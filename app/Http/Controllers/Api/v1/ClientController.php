<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ClientController extends Controller
{
    /**
     * List clients pipeline with search, status filtering, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = $request->query('status');
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 100);

        $query = Client::with(['assignedTo', 'creator'])
            ->latest('last_activity_at');

        // Isolation: Regular non-super-admin users only see clients assigned to or created by them
        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }

        if ($status && in_array($status, [
            Client::STATUS_DISCOVERY,
            Client::STATUS_SCHEDULED,
            Client::STATUS_PAPERWORK_ONBOARDING,
            Client::STATUS_ONBOARDED,
            Client::STATUS_DEADEND,
            Client::STATUS_PAUSE,
        ], true)) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => collect($paginated->items())->map(fn($c) => $this->formatClient($c)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
            'links' => [
                'first' => $paginated->url(1),
                'last' => $paginated->url($paginated->lastPage()),
                'prev' => $paginated->previousPageUrl(),
                'next' => $paginated->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Show client details.
     */
    public function show(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($client, $request->user());
        $client->loadMissing(['assignedTo', 'creator', 'meetings']);

        return response()->json([
            'data' => $this->formatClient($client, true),
        ]);
    }

    /**
     * Create client in the pipeline.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_role' => ['nullable', 'string', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'progress' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:discovery,scheduled,paperwork_onboarding,onboarded,deadend,pause'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $client = Client::create([
            'company_id' => $user->company_id,
            'company_name' => $validated['company_name'],
            'contact_name' => $validated['contact_name'] ?? $validated['company_name'],
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'contact_role' => $validated['contact_role'] ?? null,
            'campaign' => $validated['campaign'] ?? 'Direct',
            'progress' => $validated['progress'] ?? null,
            'status' => $validated['status'] ?? Client::STATUS_DISCOVERY,
            'assigned_to' => $validated['assigned_to'] ?? $user->id,
            'created_by' => $user->id,
            'deal_value' => $validated['deal_value'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'message' => 'Client created successfully.',
            'data' => $this->formatClient($client),
        ], 201);
    }

    /**
     * Update client details.
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($client, $request->user());

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_role' => ['nullable', 'string', 'max:100'],
            'campaign' => ['nullable', 'string', 'max:150'],
            'progress' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:discovery,scheduled,paperwork_onboarding,onboarded,deadend,pause'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update(array_merge($validated, ['last_activity_at' => now()]));

        return response()->json([
            'message' => 'Client updated successfully.',
            'data' => $this->formatClient($client),
        ]);
    }

    /**
     * Delete client.
     */
    public function destroy(Request $request, Client $client): Response
    {
        $this->authorizeClientAccess($client, $request->user());
        $client->delete();

        return response()->noContent();
    }

    /**
     * Update client pipeline status, progress notes, and deal value.
     */
    public function updateStatus(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($client, $request->user());

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:discovery,scheduled,paperwork_onboarding,onboarded,deadend,pause'],
            'progress' => ['nullable', 'string', 'max:500'],
            'deal_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $client->update([
            'status' => $validated['status'],
            'progress' => $validated['progress'] ?? $client->progress,
            'deal_value' => array_key_exists('deal_value', $validated) ? $validated['deal_value'] : $client->deal_value,
            'notes' => $validated['notes'] ?? $client->notes,
            'last_activity_at' => now(),
        ]);

        return response()->json([
            'message' => 'Client status updated successfully.',
            'data' => $this->formatClient($client),
        ]);
    }

    /**
     * List meetings linked with this client.
     */
    public function meetings(Request $request, Client $client): JsonResponse
    {
        $this->authorizeClientAccess($client, $request->user());

        $meetings = $client->meetings()->with(['organizer', 'room'])->latest('start_time')->get();

        return response()->json([
            'data' => $meetings->map(fn($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'start_time' => $m->start_time?->toIso8601String(),
                'end_time' => $m->end_time?->toIso8601String(),
                'status' => $m->status,
                'type' => $m->type,
                'organizer' => $m->organizer?->name,
                'room' => $m->room?->name,
            ]),
        ]);
    }

    protected function formatClient(Client $c, bool $detailed = false): array
    {
        $payload = [
            'id' => $c->id,
            'company_name' => $c->company_name,
            'contact_name' => $c->contact_name,
            'contact_email' => $c->contact_email,
            'contact_phone' => $c->contact_phone,
            'contact_role' => $c->contact_role,
            'campaign' => $c->campaign,
            'progress' => $c->progress,
            'status' => $c->status,
            'deal_value' => $c->deal_value ? (float) $c->deal_value : null,
            'notes' => $c->notes,
            'last_activity_at' => $c->last_activity_at?->toIso8601String(),
            'assigned_to' => $c->assignedTo ? [
                'id' => $c->assignedTo->id,
                'name' => $c->assignedTo->name,
            ] : null,
            'created_by' => $c->creator ? [
                'id' => $c->creator->id,
                'name' => $c->creator->name,
            ] : null,
        ];

        if ($detailed) {
            $payload['meetings_count'] = $c->meetings()->count();
        }

        return $payload;
    }

    protected function authorizeClientAccess(Client $client, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        if ($client->assigned_to !== $user->id && $client->created_by !== $user->id) {
            abort(403, 'Unauthorized access to client.');
        }
    }
}
