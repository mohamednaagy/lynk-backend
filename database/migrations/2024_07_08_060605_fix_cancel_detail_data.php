<?php

use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderCancelType;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
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

        //remove nullable
        Schema::table('trader_order_cancel_details', function (Blueprint $table) {
            $table->string('cancel_step')->nullable()->change();

        });

        // first step purchase commodity if there are exist any trader histories
        $cancelTraders = TraderOrder::where('status', TraderOrderStatus::Cancelled)
            ->withCount('traderHistories')
            ->get(['id', 'data', 'status']);
        foreach ($cancelTraders as $cancelTrader) {
            //create log for each trader
            $data = json_decode($cancelTrader->data, true);
            $new_data['cancel_step'] = $cancelTrader->getCancelStep();
            $new_data['cancel_reason'] = isset($data['cancel_reason']) ? $data['cancel_reason'] : \App\Enums\TraderOrderCancelReason::Manual;
            $new_data['cancel_type'] = isset($data['cancel_reason']) && $data['cancel_reason'] == TraderOrderCancelReason::Manual ? TraderOrderCancelType::User : TraderOrderCancelType::System;
            $cancelTrader->cancelDetail()->firstOrCreate(['trader_order_id' => $cancelTrader->id], $new_data);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
