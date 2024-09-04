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
            $table->smallInteger('status')->default(\App\Enums\LocalMarket\InventoryUnitsStatus::Free)->comment('FREE=>0|RESERVED=>1')->change();
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
            $table->string('status')->default(LocalMarketInventoryUnitsStatus::Free)->comment('FREE=>0|RESERVED=>1');
        });
    }
};
