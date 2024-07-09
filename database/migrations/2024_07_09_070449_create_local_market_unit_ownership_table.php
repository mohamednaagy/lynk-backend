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
        Schema::create('local_market_unit_ownership', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_unit_id');
            $table->foreign('inventory_unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
            $table->integer('owner_type')->comment("supplier=>0|company=>1|customer=>2");
            $table->string('previous_owner');
            $table->unsignedBigInteger('owner_id');
            $table->foreign('owner_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('owner_name');
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
        Schema::dropIfExists('local_market_unit_ownership');
    }
};
