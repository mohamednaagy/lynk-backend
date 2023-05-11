<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs\V1;

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccMpoSaleCompleteNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DmccTraderHelperTrait, StopsTraderOrderOnJobFailure;

    protected $traderOrder;

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
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $this->traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->where('status', TraderOrderStatus::InProgress)
                ->whereIn('provider', ['dmcc', 'fake'])
                ->lockForUpdate()
                ->first();

            if (
                ! $this->traderOrder
                || ! $this->traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachMpoDocument)
            ) {
                return;
            }

            if (! $this->traderOrder->checkOrderStepComplete(DmccMurabhaStep::MurabhaOfferIssued)) {
                return;
            }

            $trader = Trader::driver($this->traderOrder->provider);

            $warrantDocument = $trader->getDocumentByTypeAndTransaction(
                $this->ttiId,
                'Warrant Amendment Except Warrant No'
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
            );

            $this->attachDocumentToOrder(
                $this->traderOrder,
                $warrantDocument,
                TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
            );

            $trader->createTraderOrderHistory(
                $this->traderOrder,
                FinancingOrderHistory::MurabahaSaleCompleted
            );

            $this->traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        });
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->ttiId;
    }

    public function getTraderOrder()
    {
        return TraderOrder::query()
            ->where('reference', $this->ttiId)
            ->where('status', TraderOrderStatus::InProgress)
            ->whereIn('provider', ['dmcc', 'fake'])
            ->first();
    }
}
