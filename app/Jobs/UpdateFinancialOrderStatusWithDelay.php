<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateFinancialOrderStatusWithDelay implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $traderOrder;

    protected mixed $financingOrderStatus;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($traderOrder, FinancingOrderStatus $financingOrderStatus)
    {
        $this->traderOrder = $traderOrder;
        $this->financingOrderStatus = $financingOrderStatus;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        Trader::driver('dmcc')->createSellingCommodityToCustomerDocument($traderOrder, $ttiId);
        Trader::driver('dmcc')->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);

        $this->traderOrder->order->update([
            'status' => $this->financingOrderStatus,
        ]);
    }
}
