<?php

namespace App\Jobs;

use App\Enums\MurabhaStep;
use App\Enums\Trader;
use App\Enums\TraderOrderCancelReason;
use App\Enums\TraderOrderMode;
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

    protected string $mode;

    protected string $provider;

    protected string $version;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->provider = Trader::Lynk;
        $this->version = $this->getLatestTraderVersion();
        $this->mode = TraderOrderMode::Automatic;
    }

    /**
     * Retrieve the latest trader version.
     */
    protected function getLatestTraderVersion(): string
    {
        return get_latest_version_of_trader($this->provider);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $stepToHistoriesDictionary = trader_step_histories($this->provider, $this->version);
            $lastHistoryOfStep = end($stepToHistoriesDictionary[MurabhaStep::PurchasingCommodity]);

            $expiredTraderOrders = TraderOrder::withExpiredContractSignLimit(
                $this->provider,
                $this->version,
                $lastHistoryOfStep,
                $this->mode
            )->get();

            foreach ($expiredTraderOrders as $traderOrder) {

                FacadesTrader::driver($this->provider, $this->version)
                    ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredContractSignTime);

                Log::info("Cancelled Trader Order ID: {$traderOrder->id} due to timeout.");
            }

            if ($expiredTraderOrders->isEmpty()) {
                Log::info("No expired trader orders found for provider: {$this->provider}.");
            }

        } catch (\Exception $e) {
            Log::error('Failed to check and cancel expired trader orders: '.$e->getMessage());
            // Optionally, you could throw the exception again if you want to handle it further up the chain.
        }
    }
}
