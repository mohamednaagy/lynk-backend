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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('trader_order_id')->after('id');
            $table->foreign('trader_order_id')->references('id')->on('trader_orders');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropForeign(['trader_order_id']);
            $table->dropColumn('trader_order_id');
        });
    }
};
