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
        Schema::create('trader_order_cancel_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cancelled_by');
            $table->foreign('cancelled_by')->references('id')->on('users');
            $table->unsignedBigInteger('trader_order_id');
            $table->foreign('trader_order_id')->references('id')->on('trader_orders');
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
        Schema::dropIfExists('trader_order_cancel_details');
    }
};
