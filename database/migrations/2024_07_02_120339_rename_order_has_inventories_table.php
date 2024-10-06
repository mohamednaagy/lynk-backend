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
//        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
//            $table->dropForeign('local_market_order_has_inventories_inventory_id_foreign');
//            $table->renameColumn('inventory_id', 'local_market_inventory_id');
//
//            $table->foreign('local_market_inventory_id')
//                ->references('id')
//                ->on('local_market_inventories')
//                ->constrained()
//                ->name('local_market_order_has_inventories_inventory_id_fk');
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
//        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
//            $table->dropForeign(['local_market_inventory_id']);
//            $table->renameColumn('local_market_inventory_id', 'inventory_id');
//
//            $table->foreign('inventory_id')
//                ->references('id')
//                ->on('inventories')
//                ->onDelete('cascade');
//        });
//    }
//};
