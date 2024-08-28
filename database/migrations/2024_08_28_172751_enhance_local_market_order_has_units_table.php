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
        Schema::table('local_market_order_has_units', function (Blueprint $table) {
            // TODO nagy kindly double check this file
            $table->dropForeign(['inventory_unit_id']);
            $table->dropForeign(['order_has_inventory_id']);

            $table->dropColumn('inventory_unit_id');
            $table->dropColumn('order_has_inventory_id');

            $table->unsignedBigInteger('unit_id')->after('id');
            $table->foreign('unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
            $table->unsignedBigInteger('inventory_id')->after('unit_id');
            $table->foreign('inventory_id')->references('id')->on('local_market_inventories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_order_has_units', function (Blueprint $table) {

            $table->dropForeign(['unit_id']);
            $table->dropForeign(['inventory_id']);

            $table->dropColumn('unit_id');
            $table->dropColumn('inventory_id');

            $table->unsignedBigInteger('inventory_unit_id');
            $table->foreign('inventory_unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
            $table->unsignedBigInteger('order_has_inventory_id');
            $table->foreign('order_has_inventory_id')->references('id')->on('local_market_order_has_inventories')->onDelete('cascade');
        });
    }
};
