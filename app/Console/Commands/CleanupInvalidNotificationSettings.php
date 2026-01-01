<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupInvalidNotificationSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup-invalid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up invalid notification settings with non-existent enum values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Cleaning up invalid notification settings...');

        // Get all valid notification types from the config file
        $notificationTypes = config('notification-types');
        $validTypes = array_keys($notificationTypes);

        // Find and count invalid records
        $invalidRecords = DB::table('user_notification_settings')
            ->whereNotIn('notification_type', $validTypes)
            ->get();

        $count = $invalidRecords->count();

        if ($count > 0) {
            $this->info("Found {$count} invalid notification settings records:");

            foreach ($invalidRecords as $record) {
                $this->info("- ID: {$record->id}, User ID: {$record->user_id}, Type: {$record->notification_type}");
            }

            $this->info('Deleting invalid records...');

            DB::table('user_notification_settings')
                ->whereNotIn('notification_type', $validTypes)
                ->delete();

            $this->info("Successfully deleted {$count} invalid records.");
        } else {
            $this->info('No invalid notification settings found.');
        }

        $this->info('Cleanup completed.');
    }
}
