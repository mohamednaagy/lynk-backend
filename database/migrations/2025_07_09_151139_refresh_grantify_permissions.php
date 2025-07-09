<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Run seeder
        Artisan::call('module:seed', [
            '--class' => 'GrantifyDatabaseSeeder',
            'module' => 'Grantify',
        ]);

        // Clear caches
        Artisan::call('optimize:clear');
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback logic implemented.
    }
};
