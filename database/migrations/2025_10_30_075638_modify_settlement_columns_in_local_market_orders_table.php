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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->boolean('is_commodities_settled')->default(false)->after('order_no');
            $table->dropColumn('commodities_settlement_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('is_commodities_settled');
            $table->string('commodities_settlement_status')->nullable()->after('order_no');
        });
    }
};
