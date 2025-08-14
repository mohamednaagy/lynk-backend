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
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropForeign('inventory_units_commodity_item_id_foreign');
            $table->dropForeign('inventory_units_local_market_inventory_id_foreign');
            $table->dropForeign('local_market_inventory_units_last_completed_order_id_foreign');
            $table->dropForeign('local_market_inventory_units_local_market_inventory_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items');
            $table->foreign('local_market_inventory_id')->references('id')->on('local_market_inventories');
            $table->foreign('last_completed_order_id')->references('id')->on('local_market_orders');
        });
    }
};
