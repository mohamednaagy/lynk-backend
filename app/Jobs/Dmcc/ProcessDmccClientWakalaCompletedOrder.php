<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccClientWakalaCompletedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelperTrait;

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
        DB::transaction(function () {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            $lastTraderOrder = $financingOrder->activeTraderOrder()
                ->whereIn('provider', ['dmcc', 'fake'])->first();

            if (! $lastTraderOrder) {
                return;
            }

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::MurabhaOfferIssued)) {
                return;
            }

            $trader = Trader::driver($lastTraderOrder->provider);

            $versionNo = $trader->uploadTTIDocumentAndGetVersionNumber($lastTraderOrder->reference);

            $trader->issueMurabahaPurchaseOffer(
                $lastTraderOrder->reference,
                $versionNo
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::IssueMurabahaOffer
            );

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabhaOfferIssued);

            $mpoDocument = $trader->getDocumentByTypeAndTransaction(
                $lastTraderOrder->reference,
                'Murabaha Purchase Offer Document'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            $this->attachDocumentToOrder(
                $lastTraderOrder,
                $mpoDocument,
                TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $lastTraderOrder,
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
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
