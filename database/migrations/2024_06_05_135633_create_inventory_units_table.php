<?php

use App\Enums\LocalMarket\InventoryUnitsStatus;
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
        Schema::create('inventory_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_id')->index();
            $table->unsignedBigInteger('commodity_item_id');
            $table->string('qr_code');
            $table->string('status')->default(InventoryUnitsStatus::Free)->comment('FREE=>0|RESERVED=>1');

            $table->foreign('inventory_id')->references('id')->on('inventories')->cascadeOnDelete();
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items')->cascadeOnDelete();
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
        Schema::dropIfExists('inventory_units');
    }
};
