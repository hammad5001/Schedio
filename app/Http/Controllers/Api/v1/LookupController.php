<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /**
     * List active company departments.
     */
    public function departments(Request $request): JsonResponse
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $departments->map(fn($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'code' => $d->code,
                'description' => $d->description,
            ]),
        ]);
    }

    /**
     * List active meeting rooms.
     */
    public function rooms(Request $request): JsonResponse
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get();

        return response()->json([
            'data' => $rooms->map(fn($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'code' => $r->code,
                'location' => $r->location,
                'capacity' => $r->capacity,
                'color' => $r->color,
                'amenities' => $r->amenities ?: [],
            ]),
        ]);
    }

    /**
     * List active approved team members.
     */
    public function users(Request $request): JsonResponse
    {
        $users = User::where('approval_status', User::STATUS_APPROVED)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'role_label' => $u->role_label,
                'avatar_url' => $u->avatar_url,
                'department_id' => $u->department_id,
            ]),
        ]);
    }
}
