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
            $table->string('notification_type')->index('idx_notification_type');
            $table->string('channel')->index('idx_channel');
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'notification_type', 'channel'], 'user_type_channel_unique');
        });

        $oldSettingsCount = 0;
        if (Schema::hasTable('user_notification_settings')) {
            $oldSettings = DB::table('user_notification_settings')
                ->join('notification_types', 'notification_types.id', '=', 'user_notification_settings.notification_type_id')
                ->get(['user_notification_settings.*', 'notification_types.name']);

            $oldSettingsCount = $oldSettings->count();

            $newSettings = [];
            foreach ($oldSettings as $setting) {
                $newSettings[] = [
                    'user_id' => $setting->user_id,
                    'notification_type' => $setting->name,
                    'channel' => NotificationChannel::MAIL->value,
                    'is_enabled' => $setting->is_enabled,
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
                $newSettings[] = [
                    'user_id' => $setting->user_id,
                    'notification_type' => $setting->name,
                    'channel' => NotificationChannel::PLATFORM->value,
                    'is_enabled' => false, // as per old system, only email was there
                    'created_at' => $setting->created_at,
                    'updated_at' => $setting->updated_at,
                ];
            }

            if (! empty($newSettings)) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('user_notification_settings_new')->insert($newSettings);
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        $newSettingsCount = DB::table('user_notification_settings_new')->count();

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
            $table->foreignId('notification_type_id')->constrained('notification_types');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'notification_type_id']);
        });

        if (Schema::hasTable('user_notification_settings')) {
            $newSettings = DB::table('user_notification_settings')->get();
            $notificationTypes = DB::table('notification_types')->pluck('id', 'name');

            $oldSettings = [];
            $processed = [];

            foreach ($newSettings as $setting) {
                $key = $setting->user_id.'-'.$setting->notification_type;
                if (in_array($key, $processed)) {
                    continue;
                }

                if (isset($notificationTypes[$setting->notification_type])) {
                    $emailSetting = $newSettings->where('user_id', $setting->user_id)
                        ->where('notification_type', $setting->notification_type)
                        ->where('channel', NotificationChannel::MAIL->value)
                        ->first();

                    $oldSettings[] = [
                        'user_id' => $setting->user_id,
                        'notification_type_id' => $notificationTypes[$setting->notification_type],
                        'is_enabled' => $emailSetting ? $emailSetting->is_enabled : false,
                        'created_at' => $setting->created_at,
                        'updated_at' => $setting->updated_at,
                    ];

                    $processed[] = $key;
                }
            }

            if (! empty($oldSettings)) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                DB::table('user_notification_settings_old')->insert($oldSettings);
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            $originalCount = $newSettings->unique(function ($item) {
                return $item->user_id.$item->notification_type;
            })->count();

            $rolledBackSettingsCount = DB::table('user_notification_settings_old')->count();

            if ($originalCount > 0 && $originalCount !== $rolledBackSettingsCount) {
                throw new \Exception('Data migration failed during user notification settings rollback: Mismatched record counts.');
            }
        }

        Schema::dropIfExists('user_notification_settings');
        Schema::rename('user_notification_settings_old', 'user_notification_settings');
    }
};
