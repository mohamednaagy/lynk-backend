<?php

namespace App\Jobs;

use App\Enums\Trader;
use App\Enums\TraderOrderCancelReason;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader as FacadesTrader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiredContractSignedTimeTraderOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {

            $expiredTraderOrders = TraderOrder::withExpiredContractSignLimit();
            foreach ($expiredTraderOrders as $traderOrder) {
                FacadesTrader::driver(Trader::Lynk, get_latest_version_of_trader(Trader::Lynk))
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredContractSignTime);
                
                Log::info("Cancelled Trader Order ID: {$traderOrder->id} due to timeout.");
            }

            if ($expiredTraderOrders->isEmpty()) {
                Log::info("No expired trader orders found for Lynk provider.");
            }

        } catch (\Exception $e) {
            Log::error("Failed to check and cancel expired trader orders: " . $e->getMessage());
            // Optionally, you could throw the exception again if you want to handle it further up the chain.
        }
    }
}
