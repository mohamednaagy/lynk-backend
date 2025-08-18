<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OverwriteOldTransferOwnershipToLenderDocumentToCorrectDates implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        TraderOrder::query()
            ->with('traderHistories')
            ->orderBy('id')
            ->chunk(100, function ($traderOrders) {
                $traderOrders->each(function ($traderOrder) {
                    $products = collect($traderOrder->products);
                    $date = $traderOrder->traderHistories
                        ->where('action', FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)
                        ->first()
                        ?->created_at
                        ?->toImmutable();

                    if (blank($products) || blank($date)) {
                        return;
                    }

                    if ($traderOrder->hasMedia(TraderOrderMediaCollection::TransferOwnershipToLender)) {
                        $traderOrder->clearMediaCollection(TraderOrderMediaCollection::TransferOwnershipToLender);
                    }
                });
            });
    }
}
