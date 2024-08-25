<?php

namespace App\Jobs\LocalMarket\states;

use App\Models\LocalMarketOrder;
use App\Services\LocalMarket\LoanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EligibleCommoditiesFoundStatus implements ShouldQueue
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
        // double check if we can handle this order or not
        $eligibleCommodities = $this->localMarketOrder->data;

        if ($eligibleCommodities['isLoanCovered']) {
            $loanService = new LoanService;
            $loanService->buyCommodities($this->localMarketOrder->company_id, $eligibleCommodities);
        } else {
            Log::error("Loan {$this->localMarketOrder->id} is not covered we can not move on ");
        }
    }
}
