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
        Schema::create('local_market_unit_rotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unsignedBigInteger('inventory_unit_id');
            $table->foreign('inventory_unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
            $table->string('need_update_status')->default(\App\Enums\LocalMarketUnitRotationStatus::False)->comment('True=>1|False=>0');
            $table->integer('number_of_rotations');
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
        Schema::dropIfExists('local_market_unit_rotations');
    }
};
