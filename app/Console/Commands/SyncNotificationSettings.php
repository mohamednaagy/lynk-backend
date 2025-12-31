<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class SyncNotificationSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:sync-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync notification settings for all users based on the notification-types config, ensuring all eligible notification types are present.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start syncing notification settings for all users...');

        $notificationTypes = Config::get('notification-types');
        $processedUsers = 0;
        $createdSettings = 0;
        $updatedSettings = 0;

        User::with('roles', 'notificationSettings')->chunk(100, function ($users) use ($notificationTypes, &$processedUsers, &$createdSettings, &$updatedSettings) {
            foreach ($users as $user) {
                $processedUsers++;
                $userRoles = $user->roles->pluck('name')->toArray();

                foreach ($notificationTypes as $notificationType => $notificationConfig) {
                    // Check if user has any of the roles that should receive this notification
                    $configRoleNames = array_map(function ($roleEnum) {
                        return $roleEnum->value ?? $roleEnum;
                    }, $notificationConfig['roles']);

                    $hasEligibleRole = ! empty(array_intersect($userRoles, $configRoleNames));

                    if (! $hasEligibleRole) {
                        continue;
                    }

                    foreach ($notificationConfig['channels'] as $channel => $channelConfig) {
                        // Check if this specific setting already exists
                        $existingSetting = $user->notificationSettings
                            ->where('notification_type', $notificationType)
                            ->where('channel', $channel)
                            ->first();

                        if (! $existingSetting) {
                            // Create the setting if it doesn't exist
                            UserNotificationSetting::create([
                                'user_id' => $user->id,
                                'notification_type' => $notificationType,
                                'channel' => $channel,
                                'is_enabled' => $channelConfig['default'],
                            ]);
                            $createdSettings++;
                        } elseif ($channelConfig['default'] !== $existingSetting->is_enabled && $channelConfig['default'] === true) {
                            // Update the setting if the default has changed to true
                            $existingSetting->update([
                                'is_enabled' => $channelConfig['default'],
                            ]);
                            $updatedSettings++;
                        }
                    }
                }
            }
        });

        $this->info('Successfully synced notification settings for all users.');
        $this->info("Processed {$processedUsers} users, created {$createdSettings} settings, updated {$updatedSettings} settings.");
    }
}
