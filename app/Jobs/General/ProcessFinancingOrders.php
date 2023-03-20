<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\MurabhaStep;
use App\Jobs\Dmcc\ProcessDmccMpoOrder;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
use App\Jobs\Dmcc\ProcessDmccSellingCommodityToCustomerOrder;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessFinancingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        FinancingOrder::query()
            ->whereIn('status', [
                FinancingOrderStatus::Approved,
            ])->chunk(10, function ($ordersCollection) {
                $ordersCollection->each(function (FinancingOrder $order) {
                    if (! $order->traderOrders()->count()) {
                        ProcessInProgressOrder::dispatch($order->id);
                    }

                    /** @var TraderOrder $traderOrder */
                    $traderOrder = $order->activeTraderOrder()->first();
                    if (! $traderOrder) {
                        return;
                    }

                    if (! $traderOrder->checkOrderStepComplete(MurabhaStep::PurchasingCommodity)) {
                        ProcessDmccRespondedToPtpOrder::dispatch($order->id);
                    }

                    if ($traderOrder->checkOrderStepComplete(MurabhaStep::ClientWakalaCompleted)) {
                        ProcessDmccSellingCommodityToCustomerOrder::dispatch($order->id);
                    }

                    if ($traderOrder->checkOrderStepComplete(MurabhaStep::ContractSigned)) {
                        ProcessAskClientForWakala::dispatch($order->id);
                    }

                    if ($traderOrder->checkOrderStepComplete(MurabhaStep::CommoditySoldToCustomer)) {
                        ProcessDmccMpoOrder::dispatch($order->id);
                    }
                });
            });
    }
}
