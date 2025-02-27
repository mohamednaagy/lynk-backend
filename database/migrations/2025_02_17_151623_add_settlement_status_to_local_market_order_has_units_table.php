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
        Schema::table('local_market_order_has_units', function (Blueprint $table) {
            $table->tinyInteger('settlement_status')
                ->nullable()
                ->comment('1: SoldToAnotherCustomer, 3: DeletedBySupplier')
                ->after('inventory_id')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_order_has_units', function (Blueprint $table) {
            $table->dropColumn('settlement_status');
        });
    }
};
