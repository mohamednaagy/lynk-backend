<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->tinyInteger('commodities_settlement_status')
                ->nullable()
                ->comment('1: PendingSettlement, 2: CommoditySettled, 3: SettlementConfirmed, 4: SettlementFailed, 5: SettlementCanceled')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('commodities_settlement_status');
        });
    }
};
