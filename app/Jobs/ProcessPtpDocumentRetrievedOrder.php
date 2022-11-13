<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPtpDocumentRetrievedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $driver = config('trader.default');
        DB::transaction(function () use ($driver) {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            $lastTraderOrder = $financingOrder->traderOrders()->latest()->first();

            Trader::driver($driver)->createTransferOwnershipToLenderDocument($lastTraderOrder);

            Trader::driver($driver)->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::CreateTransferOwnershipToLenderDocument
            );

            Trader::driver($driver)->updateOrderStatus($financingOrder, FinancingOrderStatus::CommodityPurchased);
        });
    }
}
