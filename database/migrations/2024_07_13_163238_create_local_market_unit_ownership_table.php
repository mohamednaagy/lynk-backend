<?php

//
//use Illuminate\Database\Migrations\Migration;
//use Illuminate\Database\Schema\Blueprint;
//use Illuminate\Support\Facades\Schema;
//
//return new class extends Migration
//{
//    /**
//     * Run the migrations.
//     *
//     * @return void
//     */
//    public function up()
//    {
//        Schema::create('local_market_unit_ownership', function (Blueprint $table) {
//            $table->id();
//            $table->unsignedBigInteger('unit_id');
//            $table->string('current_owner')->nullable();
//            $table->smallInteger('current_owner_type')->comment('Supplier => 1, Company => 2, Customer => 3');
//            $table->string('previous_owner')->nullable();
//            $table->smallInteger('previous_owner_type')->nullable()->comment('Supplier => 1, Company => 2, Customer => 3');
//            $table->timestamps();
//
//            $table->foreign('unit_id')->references('id')->on('local_market_inventory_units')->onDelete('cascade');
//        });
//    }
//
//    /**
//     * Reverse the migrations.
//     *
//     * @return void
//     */
//    public function down()
//    {
//        Schema::dropIfExists('local_market_unit_ownership');
//    }
//};
