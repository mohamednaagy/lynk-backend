<?php

use App\Enums\SystemNotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('notifications:reset-type-settings', [
            'type' => SystemNotificationType::IN_PROGRESS_ORDERS->value,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback logic implemented.
    }
};
