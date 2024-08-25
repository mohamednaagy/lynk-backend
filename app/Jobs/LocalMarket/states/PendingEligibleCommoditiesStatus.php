<?php

namespace App\Jobs\LocalMarket\states;

use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PendingEligibleCommoditiesStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $loanService = new LoanService;

        $eligibleCommodities = $loanService->getCommoditiesForLoan($this->localMarketOrder->company_id, $this->localMarketOrder->amount, $this->localMarketOrder->preferred_commodity_type);

        if ($eligibleCommodities['isLoanCovered']) {
            $this->localMarketOrder->update([
                'status' => LocalMarketOrderStatus::EligibleCommoditiesFound,
                'data' => $eligibleCommodities,
            ]);
        } else {
            $this->localMarketOrder->update([
                'status' => LocalMarketOrderStatus::NoEligibleCommoditiesFound,
                'data' => $eligibleCommodities,
            ]);
        }
    }
}
