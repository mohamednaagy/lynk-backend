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
        if (! Schema::hasTable('local_market_inventory_units')) {
            Schema::create('local_market_inventory_units', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('local_market_inventory_id')->index();
                $table->unsignedBigInteger('commodity_item_id');
                $table->string('qr_code');
                $table->smallInteger('status')->default(\App\Enums\LocalMarket\InventoryUnitsStatus::Free)->comment('FREE=>1|RESERVED=>2');
                $table->foreign('local_market_inventory_id')->references('id')->on('local_market_inventories')->onDelete('cascade');
                $table->foreign('commodity_item_id')->references('id')->on('commodity_items')->cascadeOnDelete();
                $table->unsignedBigInteger('hold_for')->default(0);
                $table->string('current_owner', 50);
                $table->smallInteger('current_owner_type');
                $table->string('previous_owner', 50)->nullable();
                $table->smallInteger('previous_owner_type')->nullable();
                $table->unsignedBigInteger('last_completed_order_id')->after('previous_owner_type')->nullable();
                $table->foreign('last_completed_order_id')->references('id')->on('local_market_orders');
                $table->softDeletes();
                $table->timestamps();
            });
        }
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
