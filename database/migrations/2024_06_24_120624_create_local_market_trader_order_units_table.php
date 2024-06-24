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
        Schema::create('local_market_trader_order_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_unit_id');
            $table->foreign('inventory_unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
            $table->unsignedBigInteger('order_has_inventory_id');
            $table->foreign('order_has_inventory_id')->references('id')->on('local_market_order_has_inventories')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_market_trader_order_units');
    }
};
