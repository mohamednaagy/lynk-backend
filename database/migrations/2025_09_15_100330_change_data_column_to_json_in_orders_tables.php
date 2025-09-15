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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        Schema::table('trader_orders', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });
    }
};
