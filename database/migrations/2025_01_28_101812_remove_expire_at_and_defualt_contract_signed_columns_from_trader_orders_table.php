<?php

use App\Enums\TraderOrderTimeLimitType;
use App\Models\TraderOrder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $traderOrders = TraderOrder::whereNotNull('expire_at')
            ->whereNotNull('default_contract_sign_time_limit')
            ->get();

        foreach ($traderOrders as $order) {
            if (! $order->timeLimits()->where('type', TraderOrderTimeLimitType::ContractSignTimeLimit)->exists()) {
                throw new ModelNotFoundException("Missing TraderLimit for TraderOrder ID: {$order->id}");
            }

            $order->update([
                'expire_at' => null,
                'default_contract_sign_time_limit' => null,
            ]);
        }

        $traderOrdersCount = TraderOrder::whereNotNull('expire_at')
            ->whereNotNull('default_contract_sign_time_limit')
            ->count();

        if ($traderOrdersCount == 0) {
            Schema::table('trader_orders', function (Blueprint $table) {
                $table->dropColumn(['expire_at', 'default_contract_sign_time_limit']);
            });
        } else {
            throw new ModelNotFoundException('There are still TraderOrders with expire_at and default_contract_sign_time_limit set.');
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dateTime('expire_at')->nullable()->after('default_contract_sign_time_limit');
            $table->integer('default_contract_sign_time_limit')->nullable()->comment('Default measurement unit (minutes)');
        });
    }
};
