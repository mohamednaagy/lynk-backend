<?php

namespace App\Jobs\LocalMarket;

use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInitiateOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        LocalMarketOrder::query()
            ->where('status', LocalMarketOrderStatus::PendingEligibleCommodities)
            ->chunk(10, function ($traderOrderCollection) {
                $traderOrderCollection->each(function (LocalMarketOrder $localMarketOrder) {
                    $loanService = new LoanService;
                    $eligibleCommodities = $loanService->getCommoditiesForLoan($localMarketOrder->company_id, $localMarketOrder->amount, $localMarketOrder->preferred_commodity_type);

                    if ($eligibleCommodities['isLoanCovered']) {
                        $localMarketOrder->update([
                            'status' => LocalMarketOrderStatus::EligibleCommoditiesFound,
                            'data' => $eligibleCommodities,
                        ]);
                    } else {
                        $localMarketOrder->update([
                            'status' => LocalMarketOrderStatus::NoEligibleCommoditiesFound,
                            'data' => $eligibleCommodities,
                        ]);
                    }
                });
            });
    }
}
