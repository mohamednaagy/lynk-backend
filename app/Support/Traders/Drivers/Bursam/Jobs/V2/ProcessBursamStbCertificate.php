<?php

namespace App\Support\Traders\Drivers\Bursam\Jobs\V2;

use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBursamStbCertificate implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrder)
    {
        //
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

        if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument)) {
            return;
        }

        Trader::driver('bursam', $traderOrder->version)->getStbCertificateDetails($traderOrder);
    }
}
