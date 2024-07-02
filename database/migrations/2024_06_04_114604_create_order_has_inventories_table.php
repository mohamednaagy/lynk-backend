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
        Schema::create('local_market_order_has_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('local_market_order_id');
            $table->foreign('local_market_order_id')->references('id')->on('local_market_orders');
            $table->unsignedBigInteger('local_market_inventory_id');
            $table->foreign('local_market_inventory_id')->references('id')->on('local_market_inventories');
            $table->integer('quantity');
            $table->decimal('price', 64, 0);
            $table->unsignedBigInteger('measurement_id');
            $table->foreign('measurement_id')->references('id')->on('measurements');
            $table->unsignedBigInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->unsignedBigInteger('location_id');
            $table->foreign('location_id')->references('id')->on('supplier_locations');
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')->references('id')->on('companies');
            $table->string('previous_owner');
            $table->unsignedBigInteger('commodity_item_id');
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items');
            $table->unsignedBigInteger('commodity_type_id');
            $table->foreign('commodity_type_id')->references('id')->on('commodity_types');
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
        Schema::dropIfExists('local_market_order_has_inventories');
    }
};
