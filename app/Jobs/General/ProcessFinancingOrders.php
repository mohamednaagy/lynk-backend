<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Jobs\Dmcc\ProcessDmccClientWakalaCompletedOrder;
use App\Jobs\Dmcc\ProcessDmccContractSignedOrder;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
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
                        FinancingOrderStatus::ClientWakalaCompleted => ProcessDmccClientWakalaCompletedOrder::dispatch($order->id),
                        FinancingOrderStatus::ContractSigned => ProcessDmccContractSignedOrder::dispatch($order->id),
                        FinancingOrderStatus::CommoditySoldToCustomer => ProcessAskClientForWakala::dispatch($order->id),
                        default => null
                    };
                });
            });
    }
}
