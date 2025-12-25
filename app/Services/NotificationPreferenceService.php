<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Closure;
use Illuminate\Support\Collection;
use Stancl\Tenancy\Database\TenantScope;

class NotificationPreferenceService
{
    public function ensureDefaults(User $user): void
    {
        $notificationTypes = collect(config('notification-types'));

        foreach ($notificationTypes as $typeKey => $typeConfig) {
            foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
                UserNotificationSetting::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'notification_type' => $typeKey,
                        'channel' => $channelKey,
                    ],
                    [
                        'is_enabled' => $channelConfig['default'] ?? false,
                    ]
                );
            }
        }
    }

    public function listForUser(User $user): Collection
    {
        $allSettings = UserNotificationSetting::where('user_id', $user->id)->get();
        $notificationTypesConfig = collect(config('notification-types'));

        return $notificationTypesConfig->map(function ($typeConfig, $typeKey) use ($allSettings) {
            $channels = collect();
            foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
                $setting = $allSettings->first(fn ($s) => $s->notification_type === $typeKey && $s->channel === $channelKey);
                $channels->put($channelKey, [
                    'enabled' => (bool) optional($setting)->is_enabled,
                    'is_editable' => $channelConfig['is_editable'] ?? true,
                ]);
            }

            return [
                'id' => $typeKey,
                'name' => $typeKey,
                'label' => __($typeConfig['label']) ?? '',
                'channels' => $channels,
                'roles' => $typeConfig['roles'] ?? [],
            ];
        })->values();
    }

    public function setEmailNotification(User $user, string $type, bool $enabled): void
    {
        $this->setEmailNotificationByTypeAndChannel($user, $type, NotificationChannel::mail, $enabled);
    }

    public function setPortalNotification(User $user, string $type, bool $enabled): void
    {
        $this->setPortalNotificationByTypeAndChannel($user, $type, NotificationChannel::platform, $enabled);
    }

    public function setEmailNotificationByTypeAndChannel(User $user, string $type, string $channel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['is_enabled' => $enabled]
        );
    }

    public function setPortalNotificationByTypeAndChannel(User $user, string $type, string $channel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['is_enabled' => $enabled]
        );
    }

    public function isEmailEnabled(User $user, string $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::mail);
    }

    public function isPortalEnabled(User $user, string $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::platform);
    }

    public function isChannelEnabled(User $user, string $type, string $channel): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->first();

        return (bool) optional($setting)->is_enabled ?? false;
    }

    public function getEnabledUsersFor(string $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::mail, $extra);
    }

    public function getEnabledUsersForPortal(string $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::platform, $extra);
    }

    public function getEnabledUsersForChannel(string $type, string $channel, ?Closure $extra = null): Collection
    {
        $usersQuery = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($type, $channel) {
                $q->where('notification_type', $type)
                    ->where('channel', $channel)
                    ->where('is_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }
}
