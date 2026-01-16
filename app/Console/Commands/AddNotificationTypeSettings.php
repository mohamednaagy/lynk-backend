<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class AddNotificationTypeSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:add-type-settings
                            {type : The notification type to add settings for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add notification settings for a specific type to users with appropriate roles, without overwriting existing settings.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $notificationType = $this->argument('type');

        // Check if the notification type exists in the config
        $notificationTypes = Config::get('notification-types');

        if (! isset($notificationTypes[$notificationType])) {
            $this->error("Notification type '{$notificationType}' does not exist in the configuration.");

            $availableTypes = array_keys($notificationTypes);
            $this->line('Available notification types: '.implode(', ', $availableTypes));

            return 1;
        }

        $notificationConfig = $notificationTypes[$notificationType];

        $this->info("Adding notification settings for type: {$notificationType}");
        $this->info('Roles that should receive this notification: '.implode(', ', array_column($notificationConfig['roles'], 'value')));

        $processedUsers = 0;
        $createdSettings = 0;

        User::with('roles', 'notificationSettings')->chunk(100, function ($users) use ($notificationType, $notificationConfig, &$processedUsers, &$createdSettings) {
            foreach ($users as $user) {
                $userRoles = $user->roles->pluck('name')->toArray();

                // Check if user has any of the roles that should receive this notification
                $configRoleNames = array_map(function ($roleEnum) {
                    return $roleEnum->value ?? $roleEnum;
                }, $notificationConfig['roles']);

                $hasEligibleRole = ! empty(array_intersect($userRoles, $configRoleNames));

                if (! $hasEligibleRole) {
                    continue;
                }

                // Process each channel for this notification type
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
                    } else {
                        $this->info("Setting already exists for user {$user->id} ({$user->email}), skipping...");
                    }
                }

                $processedUsers++;
            }
        });

        $this->info("Successfully processed notification settings for type: {$notificationType}");
        $this->info("Processed {$processedUsers} users, created {$createdSettings} new settings.");
    }
}
