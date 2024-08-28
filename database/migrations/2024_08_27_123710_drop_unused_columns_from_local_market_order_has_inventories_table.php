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
        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {

            $table->dropForeign(['measurement_id']);
            $table->dropForeign(['currency_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['commodity_item_id']);
            $table->dropForeign(['commodity_type_id']);

            $table->dropColumn([
                'measurement_id',
                'currency_id',
                'location_id',
                'previous_owner',
                'commodity_item_id',
                'commodity_type_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
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
        });
    }
};
