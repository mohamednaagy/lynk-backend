<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
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
            'settingsUpserted' => 0,
            'settingsRemoved' => 0,
        ];

        DB::transaction(function () use ($notificationConfig, $notificationType, $configRoleNames, &$result) {
            User::with('roles', 'notificationSettings')->chunk(100, function (Collection $users) use ($notificationConfig, $notificationType, $configRoleNames, &$result) {
                foreach ($users as $user) {
                    $userRoles = $user->roles->pluck('name')->toArray();

                    if ($this->userHasEligibleRole($userRoles, $configRoleNames)) {
                        $result['eligibleUsersProcessed']++;
                        $result['settingsUpserted'] += $this->upsertUserNotificationSettings($user, $notificationType, $notificationConfig);
                    } else {
                        $result['ineligibleUsersProcessed']++;
                        $result['settingsRemoved'] += $this->removeUserNotificationSettings($user, $notificationType);
                    }
                }
            });
        });

        return $result;
    }

    /**
     * Check if user has any of the roles that should receive this notification
     */
    private function userHasEligibleRole(array $userRoles, array $configRoleNames): bool
    {
        return ! empty(array_intersect($userRoles, $configRoleNames));
    }

    /**
     * Insert notification settings for a user if they don't exist
     */
    private function upsertUserNotificationSettings(User $user, string $notificationType, array $notificationConfig): int
    {
        $insertCount = 0;

        foreach ($notificationConfig['channels'] as $channel => $channelConfig) {
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
                $insertCount++;
            }
        }

        return $insertCount;
    }

    /**
     * Remove all notification settings for a user for the given notification type
     */
    private function removeUserNotificationSettings(User $user, string $notificationType): int
    {
        return UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->delete();
    }

    /**
     * Display the results of the operation
     */
    private function displayResults(string $notificationType, array $result): void
    {
        $this->info("Successfully reset notification settings for type: $notificationType");
        $this->info("Processed {$result['eligibleUsersProcessed']} eligible users, upserted {$result['settingsUpserted']} settings.");
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
