<?php

namespace App\Jobs;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDmccOrders implements ShouldQueue
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
                FinancingOrderStatus::PtpDocumentRetrieved,
                FinancingOrderStatus::ContractSigned,
            ])->chunk(10, function ($ordersCollection) {
                $ordersCollection->each(function ($order) {
                    match ($order->status->value) {
                        FinancingOrderStatus::Approved => ProcessDmccInProgressOrder::dispatch($order->id),
                        FinancingOrderStatus::ClientWakalaCompleted => ProcessDmccClientWakalaCompletedOrder::dispatch($order->id),
                        FinancingOrderStatus::RespondedToPtp => ProcessDmccRespondedToPtpOrder::dispatch($order->id),
                        FinancingOrderStatus::PtpDocumentRetrieved => ProcessPtpDocumentRetrievedOrder::dispatch($order->id),
                        FinancingOrderStatus::ContractSigned => ProcessDmccContractSignedOrder::dispatch($order->id),
                        default => null
                    };
                });
            });
    }
}
