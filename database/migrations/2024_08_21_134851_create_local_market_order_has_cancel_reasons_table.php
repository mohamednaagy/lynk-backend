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
        Schema::create('local_market_order_has_cancel_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('local_market_order_id');
            $table->foreign('order_id')->references('id')->on('local_market_orders')->onDelete('cascade');
            $table->integer('cancelled_by');
            $table->string('cancel_step');
            $table->unsignedTinyInteger('cancel_reason');
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
        Schema::dropIfExists('local_market_order_has_cancel_reasons');
    }
};
