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
        Schema::create('commodity_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name')->unique();
            $table->string('unique_name')->unique()->nullable();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('market_type')->default(\App\Enums\CommoitySupplierMarketType::Local)->comment('1 => local  , 2 => international');
            $table->unsignedTinyInteger('status')->default(\App\Enums\CommoitySupplierStatus::Inactive)->comment('1 => active  , 2 => inactive');
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
        Schema::dropIfExists('commodity_suppliers');
    }
};
