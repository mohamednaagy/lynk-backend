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
            $table->unsignedBigInteger('local_market_order_id')->after('id');
            $table->foreign('local_market_order_id')->references('id')->on('local_market_orders')->onDelete('cascade');
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
            $table->dropForeign(['local_market_order_id']);
            $table->dropColumn('local_market_order_id');
        });
    }
};
