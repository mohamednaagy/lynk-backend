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
            $table->foreign('local_market_order_id')->references('id')->on('local_market_orders')->onDelete('cascade');
            $table->unsignedBigInteger('local_market_inventory_id');
            $table->foreign('local_market_inventory_id', 'fk_lm_inventory')->references('id')->on('local_market_inventories')->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('price', 64, 0);
            $table->unsignedBigInteger('supplier_id');
            $table->foreign('supplier_id')->references('id')->on('companies');
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
