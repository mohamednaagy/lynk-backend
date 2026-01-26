<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\Role;
use App\Enums\SystemNotificationType;
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
            $this->assertFalse($setting['channels'][NotificationChannel::MAIL->value]['enabled'], "Email notification should default to disabled for {$setting['name']}");
            $this->assertFalse($setting['channels'][NotificationChannel::PLATFORM->value]['enabled'], "Portal notification should default to disabled for {$setting['name']}");
        }
    }

    public function test_email_notification_can_be_toggled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $notificationType = SystemNotificationType::ORDER_REQUIRES_APPROVAL;

        // Initially should be disabled
        $this->assertFalse($service->isEmailEnabled($user, $notificationType));

        // Toggle to enabled
        $service->setChannelNotification($user, $notificationType, NotificationChannel::MAIL, true);
        $this->assertTrue($service->isEmailEnabled($user, $notificationType));

        // Toggle back to disabled
        $service->setChannelNotification($user, $notificationType, NotificationChannel::MAIL, false);
        $this->assertFalse($service->isEmailEnabled($user, $notificationType));
    }

    public function test_channel_notification_can_be_toggled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $notificationType = SystemNotificationType::ORDER_REQUIRES_APPROVAL;

        // Initially should be disabled for mail channel
        $this->assertFalse($service->isChannelEnabled($user, $notificationType, \App\Enums\NotificationChannel::MAIL));

        // Toggle to enabled using generic method
        $service->setChannelNotification($user, $notificationType, \App\Enums\NotificationChannel::MAIL, true);
        $this->assertTrue($service->isChannelEnabled($user, $notificationType, \App\Enums\NotificationChannel::MAIL));

        // Toggle back to disabled
        $service->setChannelNotification($user, $notificationType, \App\Enums\NotificationChannel::MAIL, false);
        $this->assertFalse($service->isChannelEnabled($user, $notificationType, \App\Enums\NotificationChannel::MAIL));
    }

    public function test_portal_notification_defaults_to_disabled_and_remains_disabled()
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Admin);

        $service = app(NotificationPreferenceService::class);
        $service->ensureDefaults($user);

        $notificationType = SystemNotificationType::ORDER_REQUIRES_APPROVAL;

        // Initially should be disabled
        $this->assertFalse($service->isPortalEnabled($user, $notificationType));

        // Even if we try to enable it, it should remain disabled per requirements
        $service->setChannelNotification($user, $notificationType, NotificationChannel::PLATFORM, true);
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

        $notificationType = SystemNotificationType::ORDER_REQUIRES_APPROVAL;

        // Enable notification for admin1 only
        $service->setChannelNotification($admin1, $notificationType, NotificationChannel::MAIL, true);

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
            $mailSetting = UserNotificationSetting::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->where('channel', \App\Enums\NotificationChannel::MAIL)
                ->first();

            $this->assertNotNull($mailSetting, "Mail notification setting should exist for type: {$type->value}");
            $this->assertFalse($mailSetting->is_enabled, "Email notification should be disabled by default for {$type->value}");

            $portalSetting = UserNotificationSetting::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->where('channel', NotificationChannel::PLATFORM)
                ->first();

            $this->assertNotNull($portalSetting, "Portal notification setting should exist for type: {$type->value}");
            $this->assertFalse($portalSetting->is_enabled, "Portal notification should be disabled by default for {$type->value}");
        }
    }
}
