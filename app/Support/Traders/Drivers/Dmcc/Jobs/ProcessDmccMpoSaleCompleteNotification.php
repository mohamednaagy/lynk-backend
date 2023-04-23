<?php

namespace App\Support\Traders\Drivers\Dmcc\Jobs;

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\TraderOrderStatus;
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

class ProcessDmccMpoSaleCompleteNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DmccTraderHelperTrait;

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
            $traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->where('status', TraderOrderStatus::InProgress)
                ->whereIn('provider', ['dmcc', 'fake'])
                ->lockForUpdate()
                ->first();

            if (
                ! $traderOrder
                || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::AttachMpoDocument)
            ) {
                return;
            }

            if (! $traderOrder->checkOrderStepComplete(DmccMurabhaStep::MurabhaOfferIssued)) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

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
                TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'base64'
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
            );

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::MurabahaSaleCompleted
            );

            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
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
