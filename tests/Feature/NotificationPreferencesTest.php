<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\SystemNotificationType;
use App\Models\NotificationType;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_notification_preferences_default_to_disabled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $settings = $service->listForUser($user);

        foreach ($settings as $setting) {
            $this->assertFalse($setting['email_enabled'], "Email notification should default to disabled for {$setting['name']}");
            $this->assertFalse($setting['portal_enabled'], "Portal notification should default to disabled for {$setting['name']}");
        }
    }

    public function test_email_notification_can_be_toggled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $notificationType = NotificationType::where('name', SystemNotificationType::ORDER_REQUIRES_APPROVAL)->first();

        // Initially should be disabled
        $this->assertFalse($service->isEmailEnabled($user, $notificationType));

        // Toggle to enabled
        $service->setEmailNotificationByModel($user, $notificationType, true);
        $this->assertTrue($service->isEmailEnabled($user, $notificationType));

        // Toggle back to disabled
        $service->setEmailNotificationByModel($user, $notificationType, false);
        $this->assertFalse($service->isEmailEnabled($user, $notificationType));
    }

    public function test_portal_notification_defaults_to_disabled_and_remains_disabled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $notificationType = NotificationType::where('name', SystemNotificationType::ORDER_REQUIRES_APPROVAL)->first();

        // Initially should be disabled
        $this->assertFalse($service->isPortalEnabled($user, $notificationType));

        // Even if we try to enable it, it should remain disabled per requirements
        $service->setPortalNotificationByModel($user, $notificationType, true);
        $this->assertFalse($service->isPortalEnabled($user, $notificationType), 'Portal notifications should not be editable and remain disabled');
    }

    public function test_get_enabled_users_for_email_notifications()
    {
        $admin1 = User::factory()->create();
        $admin1->assignRole(Role::Admin);

        $admin2 = User::factory()->create();
        $admin2->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($admin1);
        $service->ensureDefaults($admin2);

        $notificationType = NotificationType::where('name', SystemNotificationType::ORDER_REQUIRES_APPROVAL)->first();

        // Enable notification for admin1 only
        $service->setEmailNotificationByModel($admin1, $notificationType, true);

        $enabledUsers = $service->getEnabledUsersFor(SystemNotificationType::ORDER_REQUIRES_APPROVAL);

        $this->assertCount(1, $enabledUsers);
        $this->assertTrue($enabledUsers->contains($admin1));
        $this->assertFalse($enabledUsers->contains($admin2));
    }

    public function test_notification_settings_are_created_for_new_users()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $adminNotificationTypes = SystemNotificationType::getAdminNotificationTypes();

        foreach ($adminNotificationTypes as $type) {
            $setting = UserNotificationSetting::where('user_id', $user->id)
                ->whereHas('type', function ($query) use ($type) {
                    $query->where('name', $type);
                })
                ->first();

            $this->assertNotNull($setting, "Notification setting should exist for type: $type");
            $this->assertFalse($setting->email_enabled, "Email notification should be disabled by default for $type");
            $this->assertFalse($setting->portal_enabled, "Portal notification should be disabled by default for $type");
        }
    }
}
