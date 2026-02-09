<?php

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
            'type' => 'in_progress_orders',
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
