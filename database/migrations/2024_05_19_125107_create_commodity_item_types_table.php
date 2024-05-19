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
        Schema::create('commodity_item_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commodity_item_id');
            $table->foreign('commodity_item_id')->references('id')->on('commodity_items');
            $table->unsignedBigInteger('commodity_type_id');
            $table->foreign('commodity_type_id')->references('id')->on('commodity_types');
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
        Schema::dropIfExists('commodity_item_types');
    }
};
