<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\FinancingOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccMpoNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, TraderHelper;

    protected string $ttiId;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->where('status', TraderOrderStatus::InProgress)
                ->whereIn('provider', ['dmcc', 'fake'])
                ->lockForUpdate()
                ->first();

            if (! $traderOrder) {
                return;
            }

            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($traderOrder->financing_order_id);

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::MurabhaOfferIssued)) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

            $versionNo = $trader->uploadTTIDocumentAndGetVersionNumber($this->ttiId);

            $trader->issueMurabahaPurchaseOffer(
                $this->ttiId,
                $versionNo
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::IssueMurabahaOffer
            );

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabhaOfferIssued);

            $mpoDocument = $trader->getDocumentByTypeAndTransaction(
                $this->ttiId,
                'Murabaha Purchase Offer Document'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $mpoDocument,
                FinancingOrderMediaCollection::MurabahaPurchaseOrder,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachMpoDocument
            );

            $warrantDocument = $trader->getDocumentByTypeAndTransaction(
                $this->ttiId,
                'Warrant Amendment Except Warrant No'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
            );

            $this->attachDocumentToOrder(
                $traderOrder,
                $warrantDocument,
                FinancingOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
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
        return [new WithoutOverlapping('dmccTtiId'.$this->ttiId)];
    }
}
