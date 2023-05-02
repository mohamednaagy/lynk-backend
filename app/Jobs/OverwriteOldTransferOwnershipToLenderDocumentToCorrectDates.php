<?php

namespace App\Jobs;

use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

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
            ->orderBy('id')
            ->chunk(100, function ($traderOrders) {
                $traderOrders->map(function ($traderOrder) {
                    $products = collect($traderOrder->products);
                    if (blank($products)) {
                        return;
                    }

                    $traderOrder->clearMediaCollection(TraderOrderMediaCollection::TransferOwnershipToLender);
                    $trader = Trader::driver($traderOrder->provider);
                    $separator = ' و ';
                    $amount = $traderOrder->order->amount->formatByDecimal();
                    $previous_owner = $products->pluck('previous_owner')->implode($separator);
                    $product_name = $products->pluck('product')->implode($separator);

                    $trader->storeOrderDocumentAsPdf(
                        'transfer-ownership-to-lender',
                        [
                            'order_id' => $traderOrder->order->id,
                            'products' => $traderOrder->products,
                            'reference_number' => $traderOrder->id,
                            'company_name' => $traderOrder->order->company()->withTrashed()->first()?->name,
                            'order_number' => $traderOrder->financing_order_id,
                            'amount' => $amount,
                            'previous_owner' => $previous_owner,
                            'product_name' => $product_name,
                            'date' => Carbon::now('Asia/Riyadh')->toDateString(),
                            'time' => Carbon::now('Asia/Riyadh')->toTimeString(),
                        ],
                        $traderOrder,
                        TraderOrderMediaCollection::TransferOwnershipToLender
                    );
                });
            });
    }
}
