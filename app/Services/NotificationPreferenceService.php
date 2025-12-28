<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\SystemNotificationType;
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
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->get()->all();

        /* TODO: refactor this */
        foreach ($notificationTypes as $typeKey => $typeConfig) {
            foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
                if (! empty($typeConfig['roles']) && ! empty(array_intersect($typeConfig['roles'], $roles))) {
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
    }

    public function listForUser(User $user): Collection
    {
        $allSettings = UserNotificationSetting::where('user_id', $user->id)->get();
        $notificationTypesConfig = collect(config('notification-types'));
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->get()->all();

        return $notificationTypesConfig->map(function ($typeConfig, $typeKey) use ($allSettings, $roles) {
            if (! empty($typeConfig['roles']) && ! empty(array_intersect($typeConfig['roles'], $roles))) {
                $channels = collect();
                foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
                    $setting = $allSettings->first(fn (UserNotificationSetting $s) => $s->notification_type === $typeKey && $s->channel === $channelKey);
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
            }

            return null;
        })->filter()->values();
    }

    public function setEmailNotification(User $user, SystemNotificationType $type, bool $enabled): void
    {
        $this->setNotificationByTypeAndChannel($user, $type, NotificationChannel::MAIL, $enabled);
    }

    public function setPortalNotification(User $user, SystemNotificationType $type, bool $enabled): void
    {
        $this->setNotificationByTypeAndChannel($user, $type, NotificationChannel::PLATFORM, $enabled);
    }

    public function setNotificationByTypeAndChannel(User $user, SystemNotificationType $type, NotificationChannel $channel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['is_enabled' => $enabled]
        );
    }

    public function isEmailEnabled(User $user, string $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::MAIL);
    }

    public function isPortalEnabled(User $user, string $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::PLATFORM);
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
        return $this->getEnabledUsersForChannel($type, NotificationChannel::MAIL, $extra);
    }

    public function getEnabledUsersForPortal(string $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::PLATFORM, $extra);
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
