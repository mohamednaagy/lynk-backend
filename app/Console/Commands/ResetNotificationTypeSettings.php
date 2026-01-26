<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResetNotificationTypeSettings extends Command
{
    protected $signature = 'notifications:reset-type-settings
                            {type : The notification type to reset settings for}';

    protected $description = 'Reset notification settings for a specific type. Only inserts settings for users with eligible roles and removes settings for users without eligible roles.';

    public function handle(): int
    {
        $notificationType = $this->argument('type');

        if (! $this->validateNotificationType($notificationType)) {
            return self::FAILURE;
        }

        $notificationConfig = $this->getNotificationConfig($notificationType);
        $configRoleNames = $this->getRoleNamesFromConfig($notificationConfig);

        $this->info("Resetting notification settings for type: $notificationType");
        $this->info('Roles that should receive this notification: '.implode(', ', $notificationConfig['roles']));

        try {
            $result = $this->processNotificationSettings($notificationType, $notificationConfig, $configRoleNames);

            $this->displayResults($notificationType, $result);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->handleError($e, $notificationType);

            return self::FAILURE;
        }
    }

    /**
     * Validate if the notification type exists in the configuration
     */
    private function validateNotificationType(string $notificationType): bool
    {
        $notificationTypes = Config::get('notification-types');

        if (! isset($notificationTypes[$notificationType])) {
            $this->error("Notification type '$notificationType' does not exist in the configuration.");

            $availableTypes = array_keys($notificationTypes);
            $this->line('Available notification types: '.implode(', ', $availableTypes));

            return false;
        }

        return true;
    }

    /**
     * Get notification configuration for the given type
     */
    private function getNotificationConfig(string $notificationType): array
    {
        $notificationTypes = Config::get('notification-types');

        return $notificationTypes[$notificationType];
    }

    /**
     * Extract role names from the notification configuration
     */
    private function getRoleNamesFromConfig(array $notificationConfig): array
    {
        return array_map(function ($roleEnum) {
            return $roleEnum->value ?? $roleEnum;
        }, $notificationConfig['roles']);
    }

    /**
     * Process notification settings for all users
     *
     * @throws Throwable
     */
    private function processNotificationSettings(string $notificationType, array $notificationConfig, array $configRoleNames): array
    {
        $result = [
            'eligibleUsersProcessed' => 0,
            'ineligibleUsersProcessed' => 0,
            'settingsInserted' => 0,
            'settingsRemoved' => 0,
        ];

        DB::transaction(function () use ($notificationType, $notificationConfig, $configRoleNames, &$result) {
            // 1. Handle ineligible users: find and delete their settings in one query.
            $ineligibleUserQuery = User::whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $configRoleNames));
            $result['ineligibleUsersProcessed'] = $ineligibleUserQuery->count();
            $ineligibleUserIds = $ineligibleUserQuery->pluck('id');

            if ($ineligibleUserIds->isNotEmpty()) {
                $result['settingsRemoved'] = UserNotificationSetting::where('notification_type', $notificationType)
                    ->whereIn('user_id', $ineligibleUserIds)
                    ->delete();
            }

            // 2. Handle eligible users: collect settings to insert and use one bulk operation.
            $eligibleUserQuery = User::whereHas('roles', fn ($q) => $q->whereIn('name', $configRoleNames));
            $result['eligibleUsersProcessed'] = $eligibleUserQuery->count();

            $settingsToCreate = [];
            $eligibleUserQuery->select('id')->chunk(200, function ($users) use (&$settingsToCreate, $notificationType, $notificationConfig) {
                foreach ($users as $user) {
                    foreach ($notificationConfig['channels'] as $channel => $channelConfig) {
                        $settingsToCreate[] = [
                            'user_id' => $user->id,
                            'notification_type' => $notificationType,
                            'channel' => $channel,
                            'is_enabled' => $channelConfig['default'] ?? false,
                        ];
                    }
                }
            });

            if (! empty($settingsToCreate)) {
                // Using insertOrIgnore to only insert new records without updating existing ones.
                // Count existing records before insert for exact combinations
                $existingCount = UserNotificationSetting::where('notification_type', $notificationType)
                    ->where(function ($query) use ($settingsToCreate) {
                        foreach ($settingsToCreate as $setting) {
                            $query->orWhere(function ($q) use ($setting) {
                                $q->where('user_id', $setting['user_id'])
                                    ->where('channel', $setting['channel']);
                            });
                        }
                    })
                    ->count();

                UserNotificationSetting::insertOrIgnore($settingsToCreate);

                // Count total records after insert to get the actual number of inserted records
                $totalCount = UserNotificationSetting::where('notification_type', $notificationType)
                    ->where(function ($query) use ($settingsToCreate) {
                        foreach ($settingsToCreate as $setting) {
                            $query->orWhere(function ($q) use ($setting) {
                                $q->where('user_id', $setting['user_id'])
                                    ->where('channel', $setting['channel']);
                            });
                        }
                    })
                    ->count();

                $result['settingsInserted'] = $totalCount - $existingCount;
            }
        });

        return $result;
    }

    /**
     * Display the results of the operation
     */
    private function displayResults(string $notificationType, array $result): void
    {
        $this->info("Successfully reset notification settings for type: $notificationType");
        $this->info("Processed {$result['eligibleUsersProcessed']} eligible users, inserted {$result['settingsInserted']} settings.");
        $this->info("Processed {$result['ineligibleUsersProcessed']} ineligible users, removed {$result['settingsRemoved']} settings.");
    }

    /**
     * Handle and log errors
     */
    private function handleError(Throwable $exception, string $notificationType): void
    {
        Log::error('Failed to reset notification settings.', [
            'notification_type' => $notificationType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->error('An error occurred: '.$exception->getMessage());
    }
}
