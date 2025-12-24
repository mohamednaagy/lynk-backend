<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            // Add new columns for email and portal notifications
            // Initially with default true to preserve existing data, then update defaults later
            $table->boolean('email_enabled')->default(true)->after('notification_type_id');
            $table->boolean('portal_enabled')->default(false)->after('email_enabled');
        });

        // Copy existing is_enabled values to email_enabled
        // Note: This will be run after the columns are added
        \DB::statement('UPDATE user_notification_settings SET email_enabled = is_enabled');

        Schema::table('user_notification_settings', function (Blueprint $table) {
            // Now drop the old is_enabled column
            $table->dropColumn('is_enabled');
        });

        // Update default value for email_enabled to false as per requirements
        \DB::statement('ALTER TABLE user_notification_settings ALTER email_enabled SET DEFAULT false');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            // Restore the old is_enabled column
            $table->boolean('is_enabled')->default(false)->after('notification_type_id');

            // Drop the new columns
            $table->dropColumn(['email_enabled', 'portal_enabled']);
        });
    }
};
