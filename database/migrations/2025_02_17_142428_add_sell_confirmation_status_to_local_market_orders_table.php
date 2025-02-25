<?php

use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
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
            $table->tinyInteger('sell_confirmation_status')
                ->default(SellConfirmationStatus::Skip)
                ->comment('0: Pending, 1: Ready for Certificate, 2: Generated, 3: Error, 4: Skip (e.g., for delivery orders)')
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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('sell_confirmation_status');
        });
    }
};
