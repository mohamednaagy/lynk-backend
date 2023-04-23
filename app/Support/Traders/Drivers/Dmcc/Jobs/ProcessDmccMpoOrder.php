<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccMpoOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DmccTraderHelperTrait;

    protected mixed $traderOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($traderOrder)
    {
        $this->traderOrder = $traderOrder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrder);

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

            $versionNo = $trader->uploadTTIDocumentAndGetVersionNumber($traderOrder->reference);

            $trader->issueMurabahaPurchaseOffer(
                $traderOrder->reference,
                $versionNo
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::IssueMurabahaOffer
            );

            $mpoDocument = $trader->getDocumentByTypeAndTransaction(
                $traderOrder->reference,
                'Murabaha Purchase Offer Document'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $mpoDocument,
                TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachMpoDocument
            );
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('traderOrder'.$this->traderOrder)];
    }
}
