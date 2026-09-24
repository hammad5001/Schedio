<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Meeting;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_calendar_page_renders_successfully(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();

        $response = $this->actingAs($superAdmin)->get(route('calendar.index'));

        $response->assertStatus(200);
        $response->assertSee('Calendar Workspace');
        $response->assertSee('FullCalendar');
    }

    public function test_calendar_feed_returns_json_events(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();
        $start = Carbon::now()->subDays(5)->toIso8601String();
        $end = Carbon::now()->addDays(30)->toIso8601String();

        $response = $this->actingAs($superAdmin)->getJson(route('calendar.feed', [
            'start' => $start,
            'end' => $end,
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'start',
                'end',
                'extendedProps' => [
                    'entity_type',
                    'entity_id',
                ],
            ],
        ]);
    }

    public function test_user_can_schedule_meeting_via_calendar_api(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();
        $room = Room::first();
        $dept = Department::first();

        $startTime = Carbon::now()->addDays(4)->setTime(11, 0, 0)->toIso8601String();
        $endTime = Carbon::now()->addDays(4)->setTime(12, 0, 0)->toIso8601String();

        $response = $this->actingAs($superAdmin)->postJson(route('calendar.store'), [
            'entity_type' => 'meeting',
            'title' => 'DevOps Cloud Migration Sync',
            'description' => 'Review AWS multi-region cluster deploy.',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'room_id' => $room->id,
            'department_id' => $dept->id,
            'type' => 'hybrid',
            'meeting_link' => 'https://meet.schedio.com/devops-sync',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('meetings', [
            'title' => 'DevOps Cloud Migration Sync',
            'room_id' => $room->id,
            'organizer_id' => $superAdmin->id,
        ]);
    }

    public function test_room_conflict_prevents_double_booking(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();
        $room = Room::first();

        $startTime = Carbon::now()->addDays(8)->setTime(14, 0, 0);
        $endTime = Carbon::now()->addDays(8)->setTime(15, 0, 0);

        // First booking
        Meeting::create([
            'title' => 'Existing Room Reservation',
            'organizer_id' => $superAdmin->id,
            'room_id' => $room->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'scheduled',
            'type' => 'in_person',
        ]);

        // Attempt overlapping booking
        $response = $this->actingAs($superAdmin)->postJson(route('calendar.store'), [
            'entity_type' => 'meeting',
            'title' => 'Conflicting Reservation Attempt',
            'start_time' => $startTime->copy()->addMinutes(15)->toIso8601String(),
            'end_time' => $endTime->copy()->addMinutes(15)->toIso8601String(),
            'room_id' => $room->id,
            'type' => 'in_person',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['room_id']);
    }

    public function test_user_can_drag_and_drop_reschedule_meeting(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();
        $meeting = Meeting::where('organizer_id', $superAdmin->id)->first();

        $newStart = Carbon::now()->addDays(10)->setTime(16, 0, 0)->toIso8601String();
        $newEnd = Carbon::now()->addDays(10)->setTime(17, 0, 0)->toIso8601String();

        $response = $this->actingAs($superAdmin)->patchJson(route('calendar.update-time', [
            'type' => 'meeting',
            'id' => $meeting->id,
        ]), [
            'start_time' => $newStart,
            'end_time' => $newEnd,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $meeting->refresh();
        $this->assertEquals(Carbon::parse($newStart)->toDateTimeString(), $meeting->start_time->toDateTimeString());
    }

    public function test_user_can_delete_meeting(): void
    {
        $superAdmin = User::where('email', 'admin@schedio.com')->first();
        $meeting = Meeting::where('organizer_id', $superAdmin->id)->first();

        $response = $this->actingAs($superAdmin)->deleteJson(route('calendar.destroy', [
            'type' => 'meeting',
            'id' => $meeting->id,
        ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertSoftDeleted('meetings', ['id' => $meeting->id]);
    }
}
