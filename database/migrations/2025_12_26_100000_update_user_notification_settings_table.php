<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            $table->string('notification_type')->after('user_id');
        });

        $notificationTypes = DB::table('notification_types')->get();

        foreach ($notificationTypes as $type) {
            DB::table('user_notification_settings')
                ->where('notification_type_id', $type->id)
                ->update(['notification_type' => $type->name]);
        }

        Schema::table('user_notification_settings', function (Blueprint $table) {
            $table->dropForeign(['notification_type_id']);
            $table->dropColumn('notification_type_id');
            $table->unique(['user_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            $table->foreignId('notification_type_id')->after('user_id')->constrained('notification_types')->cascadeOnDelete();
            $table->dropColumn('notification_type');
            $table->dropUnique(['user_id', 'notification_type']);
        });
    }
};
