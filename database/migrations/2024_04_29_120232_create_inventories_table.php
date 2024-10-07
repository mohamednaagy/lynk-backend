<?php

use App\Enums\LocalMarket\InventoryStatus;
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
        Schema::create('local_market_inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('commodity_item_id');
            $table->unsignedBigInteger('commodity_type_id')->index();
            $table->unsignedBigInteger('supplier_location_id');
            $table->decimal('min_price', 64, 2);
            $table->decimal('max_price', 64, 2);
            $table->double('reserved_items')->default(0);
            $table->double('available_quantity')->index();
            $table->string('status')->default(\App\Enums\LocalMarket\InventoryStatus::Pending)->comment(InventoryStatus::Pending.'|'.InventoryStatus::Active.'|'.InventoryStatus::Inactive);
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items')->cascadeOnDelete();
            $table->foreign('commodity_type_id')->references('id')->on('commodity_types')->cascadeOnDelete();
            $table->foreign('supplier_location_id')->references('id')->on('supplier_locations')->cascadeOnDelete();
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
        Schema::dropIfExists('local_market_inventories');
    }
};
