<?php

namespace App\Services;

use App\Enums\SystemNotificationType;
use App\Models\NotificationType;
use App\Models\User;
use App\Models\UserNotificationSetting;
use Closure;
use Illuminate\Support\Collection;
use Stancl\Tenancy\Database\TenantScope;

class NotificationPreferenceService
{
    public function ensureDefaults(User $user): void
    {
        $types = NotificationType::whereIn('name', SystemNotificationType::getValues())
            ->get()
            ->keyBy('name');

        if ($user->isAdmin()) {
            $notificationTypes = SystemNotificationType::getAdminNotificationTypes();
        } else {
            $notificationTypes = SystemNotificationType::getLenderNotificationTypes();
        }
        foreach ($notificationTypes as $value) {
            $type = $types->get($value);

            if (! $type) {
                continue;
            }

            UserNotificationSetting::firstOrCreate(
                ['user_id' => $user->id, 'notification_type_id' => $type->id],
                [
                    'email_enabled' => false,  // Default = Disabled (OFF) as per requirements
                    'portal_enabled' => false,  // Default = Disabled (OFF), NOT editable as per requirements
                ]
            );
        }
    }

    public function listForUser(User $user): Collection
    {
        return NotificationType::query()
            ->join('user_notification_settings as uns', function ($join) use ($user) {
                $join->on('uns.notification_type_id', '=', 'notification_types.id')
                    ->where('uns.user_id', '=', $user->id);
            })
            ->select([
                'notification_types.id as id',
                'notification_types.name as name',
                'uns.email_enabled',
                'uns.portal_enabled',
            ])
            ->orderBy('notification_types.id')
            ->get();
    }

    public function setEmailNotification(User $user, string $type, bool $enabled): void
    {
        $typeModel = NotificationType::where('name', $type)->firstOrFail();

        $this->setEmailNotificationByModel($user, $typeModel, $enabled);
    }

    public function setPortalNotification(User $user, string $type, bool $enabled): void
    {
        $typeModel = NotificationType::where('name', $type)->firstOrFail();

        $this->setPortalNotificationByModel($user, $typeModel, $enabled);
    }

    public function setEmailNotificationByModel(User $user, NotificationType $typeModel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type_id' => $typeModel->id],
            ['email_enabled' => $enabled]
        );
    }

    public function setPortalNotificationByModel(User $user, NotificationType $typeModel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type_id' => $typeModel->id],
            ['portal_enabled' => $enabled]
        );
    }

    public function isEmailEnabled(User $user, NotificationType $typeModel): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type_id', $typeModel->id)
            ->first();

        return (bool) optional($setting)->email_enabled ?? false;
    }

    public function isPortalEnabled(User $user, NotificationType $typeModel): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type_id', $typeModel->id)
            ->first();

        return (bool) optional($setting)->portal_enabled ?? false;
    }

    public function getEnabledUsersFor(string $type, ?Closure $extra = null): Collection
    {
        $typeModel = NotificationType::where('name', $type)->first();
        if (! $typeModel) {
            return collect();
        }
        $usersQuery = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($typeModel) {
                $q->where('notification_type_id', $typeModel->id)->where('email_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }

    public function getEnabledUsersForPortal(string $type, ?Closure $extra = null): Collection
    {
        $typeModel = NotificationType::where('name', $type)->first();
        if (! $typeModel) {
            return collect();
        }
        $usersQuery = User::query()
            ->withoutGlobalScope(TenantScope::class)
            ->whereHas('notificationSettings', function ($q) use ($typeModel) {
                $q->where('notification_type_id', $typeModel->id)->where('portal_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }
}
