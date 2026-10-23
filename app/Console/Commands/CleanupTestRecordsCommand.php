<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\Reminder;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTestRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedio:cleanup-test-records 
                            {--company= : Company ID to restrict cleanup to} 
                            {--dry-run : Preview matching records without deleting} 
                            {--force : Force cleanup without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely purge mobile QA sandbox test records ([TEST] cancelled meetings and completed tasks)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $isDryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info('Scanning database for QA sandbox test records...');

        // 1. Identify [TEST] Cancelled Meetings
        $meetingsQuery = Meeting::withTrashed()
            ->where(function ($q) {
                $q->where('title', 'like', '[TEST]%')
                  ->orWhere('title', 'like', '%[TEST]%');
            })
            ->where('status', 'cancelled');

        if ($companyId) {
            $meetingsQuery->where('company_id', $companyId);
        }

        $testMeetings = $meetingsQuery->get();

        // 2. Identify [TEST] Completed Tasks
        $tasksQuery = Task::withTrashed()
            ->where(function ($q) {
                $q->where('title', 'like', '[TEST]%')
                  ->orWhere('title', 'like', '%[TEST]%');
            })
            ->where('status', 'completed');

        if ($companyId) {
            $tasksQuery->where('company_id', $companyId);
        }

        $testTasks = $tasksQuery->get();

        $this->table(
            ['Type', 'ID', 'Company ID', 'Status', 'Title'],
            $testMeetings->map(fn($m) => ['Meeting', $m->id, $m->company_id, $m->status, $m->title])
                ->concat($testTasks->map(fn($t) => ['Task', $t->id, $t->company_id, $t->status, $t->title]))
        );

        $this->line('');
        $this->info("Found: {$testMeetings->count()} cancelled [TEST] meetings and {$testTasks->count()} completed [TEST] tasks.");

        if ($testMeetings->isEmpty() && $testTasks->isEmpty()) {
            $this->info('No matching sandbox [TEST] records found to clean up.');
            return Command::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn('[DRY-RUN] No records were modified.');
            return Command::SUCCESS;
        }

        if (! $force && ! $this->confirm('Are you sure you want to permanently delete these sandbox test records?')) {
            $this->warn('Cleanup aborted by user.');
            return Command::SUCCESS;
        }

        $deletedMeetingsCount = 0;
        $deletedTasksCount = 0;

        DB::transaction(function () use ($testMeetings, $testTasks, &$deletedMeetingsCount, &$deletedTasksCount) {
            foreach ($testMeetings as $meeting) {
                // Dismiss associated polymorphic reminders
                Reminder::where('remindable_type', Meeting::class)
                    ->where('remindable_id', $meeting->id)
                    ->delete();

                // Force delete test meeting (bypassing soft deletes)
                $meeting->forceDelete();
                $deletedMeetingsCount++;
            }

            foreach ($testTasks as $task) {
                $task->forceDelete();
                $deletedTasksCount++;
            }

            AuditLog::create([
                'company_id' => $testMeetings->first()?->company_id ?? $testTasks->first()?->company_id ?? 1,
                'user_id' => null,
                'action' => 'sandbox_test_cleanup',
                'description' => "Safely purged {$deletedMeetingsCount} [TEST] cancelled meetings and {$deletedTasksCount} [TEST] completed tasks.",
                'ip_address' => '127.0.0.1',
            ]);
        });

        $this->info("Successfully cleaned up {$deletedMeetingsCount} [TEST] cancelled meetings and {$deletedTasksCount} [TEST] completed tasks.");

        return Command::SUCCESS;
    }
}
