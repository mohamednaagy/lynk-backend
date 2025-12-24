<?php

namespace App\Services;

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
        if ($user->isAdmin()) {
            $notificationTypes = SystemNotificationType::getAdminNotificationTypes();
        } else {
            $notificationTypes = SystemNotificationType::getLenderNotificationTypes();
        }
        foreach ($notificationTypes as $value) {
            UserNotificationSetting::firstOrCreate(
                ['user_id' => $user->id, 'notification_type' => $value],
                [
                    'email_enabled' => false,  // Default = Disabled (OFF) as per requirements
                    'portal_enabled' => false,  // Default = Disabled (OFF), NOT editable as per requirements
                ]
            );
        }
    }

    public function listForUser(User $user): Collection
    {
        $settings = UserNotificationSetting::where('user_id', $user->id)->get();
        $notificationTypes = collect(config('notification-types'));

        return $settings->map(function (UserNotificationSetting $setting) use ($notificationTypes) {
            $notificationType = $notificationTypes->get($setting->notification_type);

            return [
                'id' => $setting->notification_type,
                'name' => $setting->notification_type,
                'label' => $notificationType['label'] ?? '',
                'email_enabled' => $setting->email_enabled,
                'portal_enabled' => $setting->portal_enabled,
            ];
        });
    }

    public function setEmailNotification(User $user, string $type, bool $enabled): void
    {
        $this->setEmailNotificationByType($user, $type, $enabled);
    }

    public function setPortalNotification(User $user, string $type, bool $enabled): void
    {
        $this->setPortalNotificationByType($user, $type, $enabled);
    }

    public function setEmailNotificationByType(User $user, string $type, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type],
            ['email_enabled' => $enabled]
        );
    }

    public function setPortalNotificationByType(User $user, string $type, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type' => $type],
            ['portal_enabled' => $enabled]
        );
    }

    public function isEmailEnabled(User $user, string $type): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->first();

        return (bool) optional($setting)->email_enabled ?? false;
    }

    public function isPortalEnabled(User $user, string $type): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->first();

        return (bool) optional($setting)->portal_enabled ?? false;
    }

    public function getEnabledUsersFor(string $type, ?Closure $extra = null): Collection
    {
        $usersQuery = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($type) {
                $q->where('notification_type', $type)->where('email_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }

    public function getEnabledUsersForPortal(string $type, ?Closure $extra = null): Collection
    {
        $usersQuery = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($type) {
                $q->where('notification_type', $type)->where('portal_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }
}
