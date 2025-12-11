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
                ['is_enabled' => true]
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
            ->select(['notification_types.id as id', 'notification_types.name as name', 'uns.is_enabled'])
            ->orderBy('notification_types.id')
            ->get();
    }

    public function set(User $user, string $type, bool $enabled): void
    {
        $typeModel = NotificationType::where('name', $type)->firstOrFail();

        $this->setByModel($user, $typeModel, $enabled);
    }

    public function setByModel(User $user, NotificationType $typeModel, bool $enabled): void
    {
        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id, 'notification_type_id' => $typeModel->id],
            ['is_enabled' => $enabled]
        );
    }

    public function isEnabled(User $user, NotificationType $typeModel): bool
    {
        $setting = UserNotificationSetting::where('user_id', $user->id)
            ->where('notification_type_id', $typeModel->id)
            ->first();

        return (bool) optional($setting)->is_enabled ?? true;
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
                $q->where('notification_type_id', $typeModel->id)->where('is_enabled', true);
            });

        if ($extra) {
            $usersQuery->where($extra);
        }

        return $usersQuery->get();
    }
}
