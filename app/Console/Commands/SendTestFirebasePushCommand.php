<?php

namespace App\Console\Commands;

use App\Services\FirebaseCloudMessagingService;
use Illuminate\Console\Command;

class SendTestFirebasePushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firebase:test-push 
                            {target : Registered user email address or direct FCM device token}
                            {--title=Schedio Android Test Alert : Custom push title}
                            {--body=This is a verified test push notification from Schedio production API. : Custom push body}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch a controlled test push notification to verify Android Flutter delivery';

    /**
     * Execute the console command.
     */
    public function handle(FirebaseCloudMessagingService $fcm): int
    {
        $target = $this->argument('target');
        $title = $this->option('title');
        $body = $this->option('body');

        $this->info("Initiating test push notification for target: {$target}");

        if (!$fcm->isConfigured()) {
            $this->warn('Warning: Firebase service account credentials file is not detected at storage/app/firebase/service-account.json or in FIREBASE_CREDENTIALS_JSON.');
            $this->line('To complete real device transmission, place your Firebase service account JSON on the server.');
        }

        $result = $fcm->sendTestNotification($target);

        if (!empty($result['success'])) {
            $this->info('✓ Test notification dispatched successfully!');
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        $this->error('Failed to dispatch test notification: ' . ($result['message'] ?? $result['error'] ?? 'Unknown error'));
        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return Command::FAILURE;
    }
}
