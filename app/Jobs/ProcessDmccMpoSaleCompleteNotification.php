<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccMpoSaleCompleteNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
            $traderOrder = TraderOrder::query()->where('reference', $ttiId)->first();
            if (! $traderOrder) {
                return;
            }
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($traderOrder->financing_order_id);

            if ($financingOrder->status->value !== FinancingOrderStatus::MurabhaOfferIssued) {
                return;
            }

            $mpoDocument = Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->getDocumentByTypeAndTransaction(
                $ttiId,
                'Murabaha Purchase Offer Document'
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->attachDocumentToOrder(
                $traderOrder,
                $mpoDocument,
                'murabha_purchase_order',
                'base64'
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachMpoDocument
            );

            $warrantDocument = Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->getDocumentByTypeAndTransaction(
                $ttiId,
                'Warrant Amendment Except Warrant No'
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->attachDocumentToOrder(
                $traderOrder,
                $warrantDocument,
                'warrant_amendment_except_warrant_no',
                'base64'
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
            );

            Trader::driver(config('trader.default') == 'fake_dmcc' ? 'fake_dmcc' : 'dmcc')->updateOrderStatus($financingOrder, FinancingOrderStatus::MurabahaSaleCompleted);
        });
    }
}
