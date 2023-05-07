<?php

namespace App\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
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

                    $separator = ' و ';
                    $amount = $traderOrder->order->amount->formatByDecimal();
                    $previousOwner = $products->pluck('previous_owner')->implode($separator);
                    $productName = $products->pluck('product')->implode($separator);

                    $trader = Trader::driver($traderOrder->provider);
                    $trader->storeOrderDocumentAsPdf(
                        'transfer-ownership-to-lender',
                        [
                            'order_id' => $traderOrder->order->id,
                            'products' => $traderOrder->products,
                            'reference_number' => $traderOrder->id,
                            'company_name' => $traderOrder->order->company()->withTrashed()->first()?->name,
                            'order_number' => $traderOrder->financing_order_id,
                            'amount' => $amount,
                            'previous_owner' => $previousOwner,
                            'product_name' => $productName,
                            'date' => $date->tz('Asia/Riyadh')->toDateString(),
                            'time' => $date->tz('Asia/Riyadh')->toTimeString(),
                        ],
                        $traderOrder,
                        TraderOrderMediaCollection::TransferOwnershipToLender
                    );
                });
            });
    }
}
