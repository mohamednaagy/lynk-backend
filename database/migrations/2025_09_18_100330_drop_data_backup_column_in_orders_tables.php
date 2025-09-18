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
        // Drop backup columns
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('data_backup');
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('data_backup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add backup columns
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->text('data_backup')->nullable()->after('data');
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->text('data_backup')->nullable()->after('data');
        });
        
        // Backup existing data
        DB::statement('UPDATE local_market_orders SET data_backup = data');
        DB::statement('UPDATE trader_orders SET data_backup = data;');
    }
};
