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
            $table->string('previous_owner', 50)->nullable()->after('current_owner_type');
            $table->smallInteger('previous_owner_type')->nullable()->after('previous_owner');
            $table->unsignedBigInteger('last_completed_order_id')->nullable()->after('previous_owner_type');
            $table->foreign('last_completed_order_id')->references('id')->on('local_market_orders');
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
            $table->dropForeign(['last_completed_order_id']);
            $table->dropColumn(['last_completed_order_id', 'previous_owner_type', 'previous_owner']);
        });
    }
};
