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

        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->index('hold_for');
            $table->index('qr_code');
            $table->index('status');
            $table->index('local_market_inventory_id');
            $table->index('last_completed_order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            //
        });
    }
};
