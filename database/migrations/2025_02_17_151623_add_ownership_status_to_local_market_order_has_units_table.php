<?php

use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
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
            $table->tinyInteger('ownership_status')
                ->default(UnitOwnershipStatus::Owner)
                ->comment('1: Owner, 2: Sold, 3: Deleted by supplier')
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
            $table->dropColumn('ownership_status');
        });
    }
};
