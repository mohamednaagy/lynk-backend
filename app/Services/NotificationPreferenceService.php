<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\Role;
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
        return $notificationTypesConfig->filter(function ($typeConfig) use ($userRoles) {
            return ! empty($typeConfig['roles']) && ! empty(array_intersect($typeConfig['roles'], $userRoles));
        });
    }

    private function buildChannelSettings(array $typeConfig, string $typeKey, Collection $allSettings): array
    {
        $channels = [];
        foreach ($typeConfig['channels'] as $channelKey => $channelConfig) {
            $setting = $allSettings->where('notification_type', $typeKey)
                ->where('channel', $channelKey)
                ->first();

            $channels[$channelKey] = [
                'enabled' => (bool) optional($setting)->is_enabled,
                'is_editable' => $channelConfig['is_editable'] ?? true,
            ];
        }

        return $channels;
    }

    /**
     * Check if a specific channel is enabled for a notification type
     */
    public function isChannelEnabled(User $user, SystemNotificationType $type, NotificationChannel $channel): bool
    {
        /** @var UserNotificationSetting|null $setting */
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->first();

        return $setting->is_enabled ?? false;
    }

    /**
     * Set notification for a specific channel
     */
    public function setChannelNotification(User $user, SystemNotificationType $type, NotificationChannel $channel, bool $enabled): void
    {
        // Validate the notification type exists in config
        $this->validateNotificationType($type);

        // Get the notification settings configuration
        $channelConfig = $this->getNotificationChannelConfig($type, $channel);

        if (isset($channelConfig['is_editable']) && ! $channelConfig['is_editable']) {
            return;
        }

        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
            ['is_enabled' => $enabled]
        );
    }

    /**
     * Validate that a notification type exists in the configuration
     *
     * @throws \InvalidArgumentException
     */
    private function validateNotificationType(SystemNotificationType $type): void
    {
        $typeConfig = config("notification-types.{$type->value}");
        if ($typeConfig === null) {
            throw new \InvalidArgumentException("Invalid notification type: {$type->value}");
        }
    }

    /**
     * Get the configuration for a specific notification type and channel
     */
    private function getNotificationChannelConfig(SystemNotificationType $type, NotificationChannel $channel): ?array
    {
        return config("notification-types.{$type->value}.channels.{$channel->value}");
    }

    public function setNotificationByTypeAndChannel(User $user, SystemNotificationType $type, NotificationChannel $channel, bool $enabled): void
    {
        $this->setChannelNotification($user, $type, $channel, $enabled);
    }

    public function isEmailEnabled(User $user, SystemNotificationType $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::MAIL);
    }

    public function isPortalEnabled(User $user, SystemNotificationType $type): bool
    {
        return $this->isChannelEnabled($user, $type, NotificationChannel::PLATFORM);
    }

    public function getEnabledUsersFor(SystemNotificationType $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForType($type, null, $extra);
    }

    public function getEnabledAdminsFor(SystemNotificationType $type): Collection
    {
        return $this->getEnabledUsersForType($type, null, function ($query) {
            $query->role(Role::Admin);
        });
    }

    public function getEnabledUsersForPortal(SystemNotificationType $type, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForChannel($type, NotificationChannel::PLATFORM, $extra);
    }

    public function getEnabledUsersForChannel(SystemNotificationType $type, NotificationChannel $channel, ?Closure $extra = null): Collection
    {
        return $this->getEnabledUsersForType($type, $channel, $extra);
    }

    public function getEnabledUsersForType(SystemNotificationType $type, ?NotificationChannel $channel = null, ?Closure $extra = null): Collection
    {
        return User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($type, $channel) {
                $q->where('notification_type', $type)
                    ->when($channel, function ($query) use ($channel) {
                        $query->where('channel', $channel);
                    })
                    ->where('is_enabled', true);
            })
            ->when($extra, function ($query) use ($extra) {
                $query->where($extra);
            })
            ->get();
    }

    public function getUserNotificationTypeSettings($notifiable, SystemNotificationType $type): Collection
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
