<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class FillNotificationSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:fill-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fill notification settings for all users based on the notification-types config.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Start filling notification settings for all users...');

        $notificationTypes = Config::get('notification-types');

        User::with('roles', 'notificationSettings')->chunk(100, function ($users) use ($notificationTypes) {
            foreach ($users as $user) {
                $userRoles = $user->roles->pluck('name')->toArray();

                foreach ($notificationTypes as $notificationType => $notificationConfig) {
                    if (empty(array_intersect($userRoles, $notificationConfig['roles']))) {
                        continue;
                    }

                    foreach ($notificationConfig['channels'] as $channel => $channelConfig) {
                        $settingExists = $user->notificationSettings
                            ->where('notification_type', $notificationType)
                            ->where('channel', $channel)
                            ->count() > 0;

                        if (! $settingExists) {
                            UserNotificationSetting::firstOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'notification_type' => $notificationType,
                                    'channel' => $channel,
                                ],
                                [
                                    'is_enabled' => $channelConfig['default'],
                                ]
                            );
                        }
                    }
                }
            }
        });

        $this->info('Successfully filled notification settings for all users.');
    }
}
