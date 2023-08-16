<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccMpoOrder implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

    protected $traderOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected $traderOrderId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->traderOrder = TraderOrder::query()->lockForUpdate()->findOrFail($this->traderOrderId);

            if (! $this->traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)) {
                return;
            }

            $trader = Trader::driver($this->traderOrder->provider);

            $versionNo = $trader->uploadTTIDocumentAndGetVersionNumber($this->traderOrder->reference);

            $trader->issueMurabahaPurchaseOffer(
                $this->traderOrder->reference,
                $versionNo
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::IssueMurabahaOffer
            );

            $mpoDocument = $trader->getDocumentByTypeAndTransaction(
                $this->traderOrder->reference,
                'Murabaha Purchase Offer Document'
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            $this->attachDocumentToOrder(
                $this->traderOrder,
                $mpoDocument,
                TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::AttachMpoDocument
            );
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}
