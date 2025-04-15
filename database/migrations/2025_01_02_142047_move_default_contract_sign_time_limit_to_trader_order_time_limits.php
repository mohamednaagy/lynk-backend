<?php

use App\Enums\Trader;
use App\Enums\TraderOrderTimeLimitAction;
use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrderTimeLimit;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Moves existing expire_at data from trader_orders to the new trader_order_time_limits table
     * and removes the expire_at column afterwards.
     *
     * @return void
     */
    public function up()
    {
        DB::table('trader_orders')
            ->whereNotNull('expire_at')
            ->orderBy('id')
            ->chunk(100, function ($traderOrders) {
                foreach ($traderOrders as $traderOrder) {
                    $isExpired = Carbon::parse($traderOrder->expire_at)->isPast();
                    $isAutoCancel = $traderOrder->isAutomaticMode() && $traderOrder->provider === Trader::Lynk;

                    TraderOrderTimeLimit::create([
                        'trader_order_id' => $traderOrder->id,
                        'type' => TraderOrderTimeLimitType::ContractSignTimeLimit,
                        'status' => $isExpired
                            ? TraderOrderTimeLimitStatus::Expired
                            : TraderOrderTimeLimitStatus::Pending,
                        'effective_at' => $traderOrder->expire_at,
                        'default_value' => (int) $traderOrder->default_contract_sign_time_limit / 60,
                        'action' => $isAutoCancel
                            ? TraderOrderTimeLimitAction::AutoCancelOrder
                            : TraderOrderTimeLimitAction::NoActionNeeded,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     * Restores the expire_at column to trader_orders table.
     *
     * @return void
     */
    public function down()
    {
        // Restore data from trader_order_time_limits back to trader_orders
        DB::table('trader_order_time_limits')
            ->where('type', TraderOrderTimeLimitType::ContractSignTimeLimit)
            ->orderBy('id')
            ->chunk(100, function ($timeLimits) {
                foreach ($timeLimits as $timeLimit) {
                    DB::table('trader_orders')
                        ->where('id', $timeLimit->trader_order_id)
                        ->update([
                            'expire_at' => $timeLimit->effective_at,
                        ]);
                }
            });

        // Clean up the migrated records
        DB::table('trader_order_time_limits')
            ->where('type', TraderOrderTimeLimitType::ContractSignTimeLimit)
            ->delete();
    }
};
