<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Event;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\Reminder;
use App\Models\Room;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Core Departments
        $engineering = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'description' => 'Software engineering, DevOps, and technical architecture.',
            'is_active' => true,
        ]);

        $product = Department::create([
            'name' => 'Product & Design',
            'code' => 'PROD',
            'description' => 'Product management, UX/UI design, and user research.',
            'is_active' => true,
        ]);

        $operations = Department::create([
            'name' => 'Operations & HR',
            'code' => 'OPS',
            'description' => 'Human resources, office management, and business operations.',
            'is_active' => true,
        ]);

        // 2. Seed Users
        $admin = User::create([
            'name' => 'Alex Rivera',
            'title' => 'Chief Technology Officer',
            'email' => 'admin@schedio.com',
            'phone' => '+1 (555) 019-2834',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'role' => 'admin',
            'avatar_url' => null,
            'timezone' => 'America/New_York',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $manager = User::create([
            'name' => 'Sarah Chen',
            'title' => 'Head of Product',
            'email' => 'sarah.chen@schedio.com',
            'phone' => '+1 (555) 014-9921',
            'password' => Hash::make('password'),
            'department_id' => $product->id,
            'role' => 'manager',
            'avatar_url' => null,
            'timezone' => 'America/New_York',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $employee1 = User::create([
            'name' => 'Marcus Vance',
            'title' => 'Senior Full Stack Engineer',
            'email' => 'marcus.v@schedio.com',
            'phone' => '+1 (555) 018-3729',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'role' => 'employee',
            'avatar_url' => null,
            'timezone' => 'America/New_York',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $employee2 = User::create([
            'name' => 'Elena Rostova',
            'title' => 'Lead Product Designer',
            'email' => 'elena.r@schedio.com',
            'phone' => '+1 (555) 012-4411',
            'password' => Hash::make('password'),
            'department_id' => $product->id,
            'role' => 'employee',
            'avatar_url' => null,
            'timezone' => 'America/New_York',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Assign department managers
        $engineering->update(['manager_id' => $admin->id]);
        $product->update(['manager_id' => $manager->id]);
        $operations->update(['manager_id' => $admin->id]);

        // 3. Seed Meeting Rooms
        $boardroom = Room::create([
            'name' => 'Apollo Boardroom',
            'code' => 'ROOM-APOLLO',
            'location' => 'Floor 4, East Wing',
            'capacity' => 16,
            'amenities' => ['4K Dual Displays', 'Video Conference Bar', 'Whiteboard', 'Airplay & HDMI', 'Coffee Station'],
            'color' => '#6366f1',
            'is_active' => true,
        ]);

        $focusRoom = Room::create([
            'name' => 'Orion Focus Pod',
            'code' => 'ROOM-ORION',
            'location' => 'Floor 3, West Wing',
            'capacity' => 4,
            'amenities' => ['Acoustic Insulation', 'Ultra-wide Monitor', 'Jabra Mic Speaker'],
            'color' => '#06b6d4',
            'is_active' => true,
        ]);

        $virtualHub = Room::create([
            'name' => 'Virtual Hub / Studio',
            'code' => 'ROOM-STUDIO',
            'location' => 'Online Dedicated Studio',
            'capacity' => 100,
            'amenities' => ['Studio Lighting', 'High-Gain Shure Mics', 'OBS Streaming Setup'],
            'color' => '#10b981',
            'is_active' => true,
        ]);

        // 4. Seed Upcoming Meetings
        $meeting1 = Meeting::create([
            'title' => 'Q4 Sprint Planning & Architecture Review',
            'description' => 'Review technical roadmap, microservice decoupling milestones, and database optimization.',
            'organizer_id' => $admin->id,
            'department_id' => $engineering->id,
            'room_id' => $boardroom->id,
            'start_time' => Carbon::now()->addDay()->setTime(10, 0, 0),
            'end_time' => Carbon::now()->addDay()->setTime(11, 30, 0),
            'timezone' => 'America/New_York',
            'status' => 'scheduled',
            'type' => 'hybrid',
            'meeting_link' => 'https://meet.schedio.com/eng-sprint-q4',
            'location' => 'Apollo Boardroom & Google Meet',
            'is_private' => false,
            'recurrence' => null,
            'metadata' => ['agenda_doc' => 'https://docs.schedio.com/eng/q4-plan'],
        ]);

        $meeting2 = Meeting::create([
            'title' => 'Design System 2.0 & Component Sync',
            'description' => 'Review dark mode token updates and Figma-to-Blade component parity.',
            'organizer_id' => $manager->id,
            'department_id' => $product->id,
            'room_id' => $focusRoom->id,
            'start_time' => Carbon::now()->addDays(2)->setTime(14, 0, 0),
            'end_time' => Carbon::now()->addDays(2)->setTime(15, 0, 0),
            'timezone' => 'America/New_York',
            'status' => 'scheduled',
            'type' => 'in_person',
            'meeting_link' => null,
            'location' => 'Orion Focus Pod',
            'is_private' => false,
            'recurrence' => null,
            'metadata' => ['figma_file' => 'https://figma.com/@schedio/design-system'],
        ]);

        // 5. Seed Meeting Participants
        MeetingParticipant::create([
            'meeting_id' => $meeting1->id,
            'user_id' => $admin->id,
            'status' => 'accepted',
            'role' => 'host',
            'response_notes' => 'Organizing presentation and agenda.',
            'responded_at' => now(),
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting1->id,
            'user_id' => $employee1->id,
            'status' => 'accepted',
            'role' => 'presenter',
            'response_notes' => 'Will present database partition benchmarks.',
            'responded_at' => now(),
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting1->id,
            'user_id' => $manager->id,
            'status' => 'tentative',
            'role' => 'attendee',
            'response_notes' => 'May arrive 10 minutes late due to client sync.',
            'responded_at' => now(),
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting2->id,
            'user_id' => $manager->id,
            'status' => 'accepted',
            'role' => 'host',
            'response_notes' => null,
            'responded_at' => now(),
        ]);

        MeetingParticipant::create([
            'meeting_id' => $meeting2->id,
            'user_id' => $employee2->id,
            'status' => 'accepted',
            'role' => 'presenter',
            'response_notes' => 'Figma mocks ready for review.',
            'responded_at' => now(),
        ]);

        // 6. Seed Events
        Event::create([
            'title' => 'Annual All-Hands & Product Keynote',
            'description' => 'Company-wide town hall celebrating annual milestones and launching Schedio v2.',
            'user_id' => $admin->id,
            'department_id' => null,
            'category' => 'milestone',
            'start_time' => Carbon::now()->addDays(5)->setTime(9, 0, 0),
            'end_time' => Carbon::now()->addDays(5)->setTime(17, 0, 0),
            'is_all_day' => true,
            'color' => '#8b5cf6',
            'location' => 'Main Auditorium & Live Stream',
            'is_company_wide' => true,
        ]);

        Event::create([
            'title' => 'Engineering Hackathon',
            'description' => '48-hour innovation sprint focusing on AI schedule optimizations.',
            'user_id' => $admin->id,
            'department_id' => $engineering->id,
            'category' => 'training',
            'start_time' => Carbon::now()->addDays(12)->setTime(9, 0, 0),
            'end_time' => Carbon::now()->addDays(14)->setTime(18, 0, 0),
            'is_all_day' => false,
            'color' => '#ec4899',
            'location' => 'Engineering Wing',
            'is_company_wide' => false,
        ]);

        // 7. Seed Tasks
        $task1 = Task::create([
            'title' => 'Prepare PostgreSQL Partitioning Benchmark Report',
            'description' => 'Document performance gains on meeting_participants and audit_logs indexed scans.',
            'assigned_to' => $employee1->id,
            'created_by' => $admin->id,
            'department_id' => $engineering->id,
            'meeting_id' => $meeting1->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => Carbon::now()->addDays(3)->setTime(18, 0, 0),
        ]);

        $task2 = Task::create([
            'title' => 'Finalize Dark Mode Token Palette',
            'description' => 'Export updated Tailwind color config for UI calendar components.',
            'assigned_to' => $employee2->id,
            'created_by' => $manager->id,
            'department_id' => $product->id,
            'meeting_id' => $meeting2->id,
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => Carbon::now()->addDays(4)->setTime(17, 0, 0),
        ]);

        // 8. Seed Reminders
        Reminder::create([
            'user_id' => $employee1->id,
            'title' => 'Upcoming Meeting: Q4 Sprint Planning',
            'message' => 'Your presentation is in 15 minutes in Apollo Boardroom.',
            'remindable_type' => Meeting::class,
            'remindable_id' => $meeting1->id,
            'remind_at' => Carbon::now()->addDay()->setTime(9, 45, 0),
            'channel' => 'in_app',
            'status' => 'pending',
            'payload' => ['urgency' => 'high', 'meeting_id' => $meeting1->id],
        ]);

        Reminder::create([
            'user_id' => $employee1->id,
            'title' => 'Task Due Soon: PostgreSQL Partitioning Report',
            'message' => 'Task is due tomorrow at 6:00 PM.',
            'remindable_type' => Task::class,
            'remindable_id' => $task1->id,
            'remind_at' => Carbon::now()->addDays(2)->setTime(9, 0, 0),
            'channel' => 'in_app',
            'status' => 'pending',
            'payload' => ['task_id' => $task1->id],
        ]);

        // 9. Seed Audit Logs
        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => Meeting::class,
            'auditable_id' => $meeting1->id,
            'description' => 'Created Q4 Sprint Planning meeting and scheduled in Apollo Boardroom.',
            'old_values' => null,
            'new_values' => [
                'title' => $meeting1->title,
                'room_id' => $meeting1->room_id,
                'start_time' => $meeting1->start_time->toISOString(),
                'end_time' => $meeting1->end_time->toISOString(),
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Schedio/1.0',
            'metadata' => ['source' => 'web_portal'],
        ]);

        AuditLog::create([
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => Task::class,
            'auditable_id' => $task1->id,
            'description' => 'Assigned task "Prepare PostgreSQL Partitioning Benchmark Report" to Marcus Vance.',
            'old_values' => null,
            'new_values' => [
                'title' => $task1->title,
                'assigned_to' => $employee1->name,
                'priority' => 'high',
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Schedio/1.0',
            'metadata' => ['source' => 'meeting_action_items'],
        ]);
    }
}
