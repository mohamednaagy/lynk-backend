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
        Schema::create('trader_order_time_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_order_id');
            $table->foreign('trader_order_id')->references('id')->on('trader_orders');
            $table->smallInteger('type');
            $table->integer('default_value')->comment('Default value in minutes');
            $table->dateTime('effective_at');
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
        Schema::dropIfExists('trader_order_time_limits');
    }
};
