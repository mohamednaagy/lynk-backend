<?php

use App\Enums\TraderOrderTimeLimitStatus;
use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrderTimeLimit;
use App\Settings\Classes\Areas\LocalMurabahaSettings;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        // Get the default contract sign time limit from settings
        $defaultContractSignTimeLimit = app(LocalMurabahaSettings::class)->default_contract_sign_time_limit;

        // Step 1: Handle expired orders
        DB::table('trader_orders')
            ->whereNotNull('expire_at')
            ->where('expire_at', '<', Carbon::now()->format('Y-m-d H:i:s'))
            ->orderBy('id') // Added orderBy clause
            ->chunk(100, function ($traderOrders) use ($defaultContractSignTimeLimit) {
                foreach ($traderOrders as $traderOrder) {
                    TraderOrderTimeLimit::create([
                        'trader_order_id' => $traderOrder->id,
                        'type' => TraderOrderTimeLimitType::ContractSignTimeLimit,
                        'status' => TraderOrderTimeLimitStatus::Expired,
                        'effective_at' => $traderOrder->expire_at,
                        'default_value' => $defaultContractSignTimeLimit,
                    ]);
                }
            });

        // Step 2: Handle pending orders
        DB::table('trader_orders')
            ->whereNotNull('expire_at')
            ->where('expire_at', '>=', Carbon::now()->format('Y-m-d H:i:s'))
            ->orderBy('id') // Added orderBy clause
            ->chunk(100, function ($traderOrders) use ($defaultContractSignTimeLimit) {
                foreach ($traderOrders as $traderOrder) {
                    TraderOrderTimeLimit::create([
                        'trader_order_id' => $traderOrder->id,
                        'type' => TraderOrderTimeLimitType::ContractSignTimeLimit,
                        'status' => TraderOrderTimeLimitStatus::Pending,
                        'effective_at' => $traderOrder->expire_at,
                        'default_value' => $defaultContractSignTimeLimit,
                    ]);
                }
            });

        // Step 3: Remove the old column
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('expire_at');
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
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dateTime('expire_at')->nullable()->after('default_contract_sign_time_limit');
        });

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
