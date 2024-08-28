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
            $table->string('currency', 4);
            $table->string('national_id')->nullable()->change();
            $table->string('customer_name')->nullable()->change();
            $table->string('external_order_no');
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
            $table->dropColumn('currency', 4);
            $table->string('national_id')->change();
            $table->string('customer_name')->change();
            $table->dropColumn('external_order_no');
        });
    }
};
