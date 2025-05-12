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
        Schema::create('trader_order_proceed_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trader_order_id')->index();
            $table->foreign('trader_order_id')->references('id')->on('trader_orders')->onDelete('cascade');
            $table->tinyInteger('case')->comment('case of proceed the trader ');
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
        Schema::dropIfExists('trader_proceed_cases');
    }
};
