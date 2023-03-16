<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoOrder;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
use App\Jobs\Dmcc\ProcessDmccSellingCommodityToCustomerOrder;
use App\Models\FinancingOrder;
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
                FinancingOrderStatus::ClientWakalaCompleted,
                FinancingOrderStatus::RespondedToPtp,
                FinancingOrderStatus::ContractSigned,
                FinancingOrderStatus::CommoditySoldToCustomer,
            ])->chunk(10, function ($ordersCollection) {
                $ordersCollection->each(function ($order) {
                    match ($order->status->value) {
                        FinancingOrderStatus::Approved => ProcessInProgressOrder::dispatch($order->id),
                        FinancingOrderStatus::RespondedToPtp => ProcessDmccRespondedToPtpOrder::dispatch($order->id),
                        FinancingOrderStatus::ClientWakalaCompleted => ProcessDmccSellingCommodityToCustomerOrder::dispatch($order->id),
                        FinancingOrderStatus::ContractSigned => ProcessAskClientForWakala::dispatch($order->id),
                        FinancingOrderStatus::CommoditySoldToCustomer => ProcessDmccMpoOrder::dispatch($order->id),
                        default => null
                    };
                });
            });
    }
}
