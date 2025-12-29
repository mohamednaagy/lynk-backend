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
            // Add individual indexes for notification_type and channel columns
            // These will improve query performance when filtering by these columns
            $table->index('notification_type', 'idx_notification_type');
            $table->index('channel', 'idx_channel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            // Drop the indexes we added
            $table->dropIndex('idx_notification_type');
            $table->dropIndex('idx_channel');
        });
    }
};
