<?php

declare(strict_types=1);

use App\Enums\SystemNotificationType;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        $notificationType = SystemNotificationType::ORDER_REQUIRES_APPROVAL->value;
        $configNotificationType = config('notification-types.'.$notificationType);

        if (empty($configNotificationType)) {
            throw new \RuntimeException("Notification type not found: {$notificationType}");
        }

        $roles = $configNotificationType['roles'];
        $channels = $configNotificationType['channels'];

        if (empty($roles)) {
            throw new \RuntimeException("No roles configured for notification type: {$notificationType}");
        }

        $eligibleUsersQuery = User::query()
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->select('id')
            ->orderBy('id');

        $eligibleUsersCount = $eligibleUsersQuery->count();

        $eligibleUsersQuery->chunkById(100, function ($users) use ($notificationType, $channels) {
            foreach ($users as $user) {
                foreach ($channels as $channel => $channelConfig) {
                    UserNotificationSetting::firstOrCreate([
                        'user_id' => $user->id,
                        'notification_type' => $notificationType,
                        'channel' => $channel,
                    ], ['is_enabled' => $channelConfig['default']]);
                }
            }
        });

        UserNotificationSetting::where('notification_type', $notificationType)
            ->whereNotIn('user_id', $eligibleUsersQuery->pluck('id')->toArray())
            ->delete();

        $createdSettingsCount = UserNotificationSetting::where('notification_type', $notificationType)
            ->count();

        $expectedSettingsCount = $eligibleUsersCount * count($channels);

        if ($createdSettingsCount !== $expectedSettingsCount) {
            throw new \RuntimeException(
                "Data migration failed: expected {$expectedSettingsCount} settings for {$eligibleUsersCount} users, got {$createdSettingsCount}."
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data migration rollback: we can't reliably restore the previous per-user state,
        // so we remove settings for this notification type.
        UserNotificationSetting::query()
            ->where('notification_type', SystemNotificationType::ORDER_REQUIRES_APPROVAL->value)
            ->delete();
    }
};
