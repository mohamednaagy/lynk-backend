<?php

use App\Enums\NotificationChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->unique(['user_id', 'notification_type', 'channel']);
        });

        $oldSettings = DB::table('user_notification_settings')->get();

        foreach ($oldSettings as $setting) {
            DB::table('user_notification_settings_new')->insert([
                'user_id' => $setting->user_id,
                'notification_type' => $setting->notification_type,
                'channel' => NotificationChannel::mail,
                'is_enabled' => $setting->email_enabled,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ]);
            DB::table('user_notification_settings_new')->insert([
                'user_id' => $setting->user_id,
                'notification_type' => $setting->notification_type,
                'channel' => NotificationChannel::platform,
                'is_enabled' => $setting->portal_enabled,
                'created_at' => $setting->created_at,
                'updated_at' => $setting->updated_at,
            ]);
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

        $newSettings = DB::table('user_notification_settings')->get()->groupBy('user_id');

        foreach ($newSettings as $userSettings) {
            $userSettingsByType = $userSettings->groupBy('notification_type');
            foreach ($userSettingsByType as $notificationTypeSettings) {
                $emailSetting = $notificationTypeSettings->where('channel', NotificationChannel::mail)->first();
                $portalSetting = $notificationTypeSettings->where('channel', NotificationChannel::platform)->first();
                DB::table('user_notification_settings_old')->insert([
                    'user_id' => $userSettings->first()->user_id,
                    'notification_type' => $notificationTypeSettings->first()->notification_type,
                    'email_enabled' => $emailSetting ? $emailSetting->is_enabled : false,
                    'portal_enabled' => $portalSetting ? $portalSetting->is_enabled : false,
                    'created_at' => $notificationTypeSettings->first()->created_at,
                    'updated_at' => $notificationTypeSettings->first()->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('user_notification_settings');
        Schema::rename('user_notification_settings_old', 'user_notification_settings');
    }
};
