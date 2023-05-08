<?php

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        FinancingOrder::whereHas(
            'traderOrders',
            fn ($query) => $query->where('status', TraderOrderStatus::InProgress)
        )
            ->orderBy('id')
            ->where('status', '!=', FinancingOrderStatus::InProgress)
            ->chunk(10, fn ($orders) => $orders->each(
                fn ($order) => $order->update(['status' => FinancingOrderStatus::InProgress])
            ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
