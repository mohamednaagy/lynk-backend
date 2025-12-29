<?php

use App\Enums\NotificationChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings_new', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->string('channel');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'notification_type', 'channel'], 'user_type_channel_unique');
        });

        $oldSettingsCount = 0;
        if (Schema::hasTable('user_notification_settings')) {
            $oldSettings = DB::table('user_notification_settings')
                ->join('notification_types', 'notification_types.id', '=', 'user_notification_settings.notification_type_id')
                ->get();

            $oldSettingsCount = $oldSettings->count();

            $newSettings = [];
            foreach ($oldSettings as $setting) {
                $newSettings[] = [
                    'user_id' => $setting->user_id,
                    'notification_type' => $setting->name,
                    'channel' => NotificationChannel::MAIL->value,
                    'is_enabled' => $setting->email_enabled,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
                $newSettings[] = [
                    'user_id' => $setting->user_id,
                    'notification_type' => $setting->name,
                    'channel' => NotificationChannel::PLATFORM->value,
                    'is_enabled' => $setting->portal_enabled,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            }

            if (! empty($newSettings)) {
                DB::table('user_notification_settings_new')->insert($newSettings);
            }
        }

        $newSettingsCount = DB::table('user_notification_settings_new')->count();

        // Each old setting becomes two new settings (mail and platform)
        if ($oldSettingsCount > 0 && $newSettingsCount !== ($oldSettingsCount * 2)) {
            throw new \Exception('Data migration failed: Mismatched record counts during user notification settings refactor.');
        }

        Schema::dropIfExists('user_notification_settings');
        Schema::rename('user_notification_settings_new', 'user_notification_settings');
    }

    public function down(): void
    {
        Schema::create('user_notification_settings_old', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->boolean('email_enabled')->default(false);
            $table->boolean('portal_enabled')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'notification_type']);
        });

        if (Schema::hasTable('user_notification_settings')) {
            $newSettings = DB::table('user_notification_settings')->get()->groupBy('user_id');

            $oldSettings = [];
            foreach ($newSettings as $userSettings) {
                $userSettingsByType = $userSettings->groupBy('notification_type');
                foreach ($userSettingsByType as $notificationTypeSettings) {
                    $emailSetting = $notificationTypeSettings->where('channel', NotificationChannel::MAIL->value)->first();
                    $portalSetting = $notificationTypeSettings->where('channel', NotificationChannel::PLATFORM->value)->first();
                    $oldSettings[] = [
                        'user_id' => $userSettings->first()->user_id,
                        'notification_type' => $notificationTypeSettings->first()->notification_type,
                        'email_enabled' => $emailSetting ? $emailSetting->is_enabled : false,
                        'portal_enabled' => $portalSetting ? $portalSetting->is_enabled : false,
                        'created_at' => $notificationTypeSettings->first()->created_at,
                        'updated_at' => $notificationTypeSettings->first()->updated_at,
                    ];
                }
            }

            if (! empty($oldSettings)) {
                DB::table('user_notification_settings_old')->insert($oldSettings);
            }

            $rolledBackSettingsCount = DB::table('user_notification_settings_old')->count();

            if ($newSettings->isNotEmpty() && count($oldSettings) !== $rolledBackSettingsCount) {
                throw new \Exception('Data migration failed during user notification settings rollback: Mismatched record counts.');
            }
        }

        Schema::dropIfExists('user_notification_settings');
        Schema::rename('user_notification_settings_old', 'user_notification_settings');
    }
};
