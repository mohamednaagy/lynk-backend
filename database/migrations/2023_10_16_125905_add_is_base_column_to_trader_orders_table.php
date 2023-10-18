<?php

use App\Models\FinancingOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
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
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->boolean('is_base')->after('status')->default(false);
        });

        FinancingOrder::chunkById(100, function (Collection $orders) {
            foreach ($orders as $order) {
                /** @var FinancingOrder $order */
                $order->activeTraderOrder()->update(['is_base' => true]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('is_base');
        });
    }
};
