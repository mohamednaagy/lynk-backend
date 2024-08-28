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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->string('buying_uuid')->nullable();
            $table->string('selling_uuid')->nullable();

            $table->renameColumn('trader_order_id', 'order_no');
        });

        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->smallInteger('current_owner_type')->after('status');
            $table->string('current_owner', 50)->after('status');
            $table->text('hold_for', 10)->after('status');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->renameColumn('order_no', 'trader_order_id');

            $table->dropColumn(['buying_uuid', 'selling_uuid'])->nullable();
        });

        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropColumn(['current_owner_type', 'current_owner', 'hold_for']);
        });

    }
};
