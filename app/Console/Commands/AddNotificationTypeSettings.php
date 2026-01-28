<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AddNotificationTypeSettings extends Command
{
    protected $signature = 'notifications:add-type-settings
                            {type : The notification type to add settings for}';

    protected $description = 'Add notification settings for a specific type to users with appropriate roles, without overwriting existing settings.';

    public function handle()
    {
        $notificationType = $this->argument('type');

        // Check if the notification type exists in the config
        $notificationTypes = Config::get('notification-types');

        if (! isset($notificationTypes[$notificationType])) {
            $this->error("Notification type '{$notificationType}' does not exist in the configuration.");

            $availableTypes = array_keys($notificationTypes);
            $this->line('Available notification types: '.implode(', ', $availableTypes));

            return self::FAILURE;
        }

        $notificationConfig = $notificationTypes[$notificationType];

        $configRoleNames = array_map(function ($roleEnum) {
            return $roleEnum->value ?? $roleEnum;
        }, $notificationConfig['roles']);

        $this->info("Adding notification settings for type: {$notificationType}");
        $this->info('Roles that should receive this notification: '.implode(', ', $notificationConfig['roles']));

        $eligibleUsersProcessed = 0;
        $createdSettings = 0;

        try {
            DB::transaction(function () use ($notificationConfig, $notificationType, $configRoleNames, &$eligibleUsersProcessed, &$createdSettings) {
                User::with('roles', 'notificationSettings')->chunk(100, function ($users) use ($notificationConfig, $notificationType, $configRoleNames, &$eligibleUsersProcessed, &$createdSettings) {
                    foreach ($users as $user) {
                        $userRoles = $user->roles->pluck('name')->toArray();

                        // Check if user has any of the roles that should receive this notification
                        $hasEligibleRole = ! empty(array_intersect($userRoles, $configRoleNames));

                        if (! $hasEligibleRole) {
                            continue;
                        }

                        $eligibleUsersProcessed++;

                        // Process each channel for this notification type
                        foreach ($notificationConfig['channels'] as $channel => $channelConfig) {
                            // Create the setting if it doesn't exist
                            $setting = UserNotificationSetting::firstOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'notification_type' => $notificationType,
                                    'channel' => $channel,
                                ],
                                [
                                    'is_enabled' => $channelConfig['default'] ?? false,
                                ]
                            );

                            if ($setting->wasRecentlyCreated) {
                                $createdSettings++;
                            }
                        }
                    }
                });
            });

            $this->info("Successfully processed notification settings for type: {$notificationType}");
            $this->info("Processed {$eligibleUsersProcessed} eligible users, created {$createdSettings} new settings.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Failed to add notification settings.', [
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->error("An error occurred: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
