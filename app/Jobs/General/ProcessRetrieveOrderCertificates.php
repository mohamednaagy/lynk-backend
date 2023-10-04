<?php

namespace App\Jobs\General;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessRetrieveOrderCertificates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
    }

    /**
     * Execute the job.
     *
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $traderOrder = TraderOrder::query()->find($this->traderOrderId);

        if (! $traderOrder) {
            $this->delete();

            return;
        }

        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        if (! $traderOrder->getFirstMedia(TraderOrderMediaCollection::TtiHoldingCertificate)) {
            $trader->getBidCertificateDetails($traderOrder);
        }

        if (! $traderOrder->getFirstMedia(TraderOrderMediaCollection::BursamSellingCommodityToCustomer)) {
            $trader->getOtcCertificateDetails($traderOrder);
        }

        if (! $traderOrder->getFirstMedia(TraderOrderMediaCollection::BursamTtiHoldingCertificate)) {
            $trader->getStbCertificateDetails($traderOrder);
        }
    }
}
