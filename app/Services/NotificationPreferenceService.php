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
        $notificationTypes = $this->getNotificationTypesForUserRoles($user);

        foreach ($notificationTypes as $typeKey => $typeConfig) {
            $this->processNotificationTypeChannels($user, $typeKey, $typeConfig);
        }
    }

    private function getNotificationTypesForUserRoles(User $user): Collection
    {
        $notificationTypes = collect(config('notification-types'));
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->get();
        $userRoleNames = $roles->pluck('name')->all();

        return $notificationTypes->filter(function ($typeConfig) use ($userRoleNames) {
            return ! empty($typeConfig['roles']) && ! empty(array_intersect($typeConfig['roles'], $userRoleNames));
        });
    }

    private function processNotificationTypeChannels(User $user, string $typeKey, array $typeConfig): void
    {
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

    public function listForUser(User $user): Collection
    {
        $allSettings = UserNotificationSetting::where('user_id', $user->id)->get();
        $notificationTypesConfig = collect(config('notification-types'));
        $roles = $user->relationLoaded('roles') ? $user->roles : $user->roles()->get();
        $userRoles = $roles->pluck('name')->all();

        $filteredNotificationTypes = $this->filterNotificationTypesForUserRoles($notificationTypesConfig, $userRoles);

        return $filteredNotificationTypes->map(function ($typeConfig, $typeKey) use ($allSettings) {
            $channels = $this->buildChannelSettings($typeConfig, $typeKey, $allSettings);

            return [
                'id' => $typeKey,
                'name' => $typeKey,
                'label' => __($typeConfig['label']),
                'channels' => $channels,
                'roles' => $typeConfig['roles'] ?? [],
            ];
        })->values();
    }

    private function filterNotificationTypesForUserRoles(Collection $notificationTypesConfig, array $userRoles): Collection
    {
        return $notificationTypesConfig->filter(function ($typeConfig, $typeKey) use ($userRoles) {
            return ! empty($typeConfig['roles']) && ! empty(array_intersect($typeConfig['roles'], $userRoles));
        });
    }

    private function buildChannelSettings(array $typeConfig, string $typeKey, Collection $allSettings): Collection
    {
        $channels = collect();
        foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
            $setting = $allSettings->where('notification_type', $typeKey)
                ->where('channel', $channelKey)
                ->first();

            $channels->put($channelKey, [
                'enabled' => (bool) optional($setting)->is_enabled,
                'is_editable' => $channelConfig['is_editable'] ?? true,
            ]);
        }

        return $channels;
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

    public function isEmailEnabled(User $user, SystemNotificationType $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::MAIL);
    }

    public function isPortalEnabled(User $user, SystemNotificationType $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::PLATFORM);
    }

    public function isChannelEnabled(User $user, SystemNotificationType $type, NotificationChannel $channel): bool
    {
        /** @var UserNotificationSetting|null $setting */
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->first();

        return $setting->is_enabled ?? false;
    }

    public function getEnabledUsersFor(SystemNotificationType $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::MAIL, $extra);
    }

    public function getEnabledUsersForPortal(SystemNotificationType $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::PLATFORM, $extra);
    }

    public function getEnabledUsersForChannel(SystemNotificationType $type, NotificationChannel $channel, ?Closure $extra = null): Collection
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

    public function getUserNotificationTypeSettings($notifiable, SystemNotificationType $type): mixed
    {
        // Get the user's notification settings for this type
        // Use the already loaded relationship if available to avoid N+1 queries
        return $notifiable->relationLoaded('notificationSettings')
            ? $notifiable->notificationSettings->where('notification_type', $type)
            : $notifiable->notificationSettings()
                ->where('notification_type', $type)
                ->get();
    }
}
