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
        Schema::create('local_market_inventory_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('local_market_inventory_id')->index();
            $table->unsignedBigInteger('commodity_item_id');
            $table->string('qr_code');
            $table->smallInteger('status')->default(\App\Enums\LocalMarket\InventoryUnitsStatus::Free)->comment('FREE=>0|RESERVED=>1');
            $table->foreign('local_market_inventory_id')->references('id')->on('local_market_inventories')->onDelete('cascade');
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items')->cascadeOnDelete();
            $table->unsignedBigInteger('hold_for')->nullable();
            $table->smallInteger('current_owner_type');
            $table->string('current_owner', 50);
            $table->softDeletes();
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
        Schema::dropIfExists('local_market_inventory_units');
    }
};
