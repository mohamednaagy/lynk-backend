<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create backup columns first
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->text('data_backup')->nullable()->after('data');
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->text('data_backup')->nullable()->after('data');
        });

        // Backup existing data
        DB::statement('UPDATE local_market_orders SET data_backup = data');
        DB::statement('UPDATE trader_orders SET data_backup = data;');

        // Update invalid JSON data
        DB::statement("UPDATE local_market_orders SET data = '{}' WHERE JSON_VALID(data) = 0 OR data IS NULL;");
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });

        DB::statement("UPDATE trader_orders SET data = '{}' WHERE JSON_VALID(data) = 0 OR data IS NULL;");
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore data from backup columns
        DB::statement('UPDATE local_market_orders SET data = data_backup WHERE data_backup IS NOT NULL;');
        DB::statement('UPDATE trader_orders SET data = data_backup WHERE data_backup IS NOT NULL;');

        // Change columns back to text
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        // Drop backup columns
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('data_backup');
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('data_backup');
        });
    }
};
