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
        // First, drop the existing foreign key constraint
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropForeign(['commodity_type_id']);
        });

        // Then, modify the column to allow signed integers (to support -1)
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->bigInteger('commodity_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // First, change back to unsigned integer
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('commodity_type_id')->nullable()->change();
        });

        // Then, restore the foreign key constraint
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->foreign('commodity_type_id')->references('id')->on('commodity_types');
        });
    }
};
