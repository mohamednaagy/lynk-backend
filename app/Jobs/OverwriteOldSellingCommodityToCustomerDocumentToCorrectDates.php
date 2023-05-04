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

class OverwriteOldSellingCommodityToCustomerDocumentToCorrectDates implements ShouldQueue
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
                        ->where('action', FinancingOrderHistory::ContractSigned)
                        ->first()
                        ?->created_at;

                    if (blank($products) || blank($date)) {
                        return;
                    }

                    if ($traderOrder->hasMedia(TraderOrderMediaCollection::SellingCommodityToCustomer)) {
                        $traderOrder->clearMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer);
                    }

                    $trader = Trader::driver($traderOrder->provider);
                    $separator = ' و ';
                    $products = collect($traderOrder->products);
                    $amount = $traderOrder->order->selling_price->formatByDecimal();
                    $customerName = $traderOrder->order->customer_name;
                    $productName = $products->pluck('product')->implode($separator);

                    $trader->storeOrderDocumentAsPdf(
                        'selling-commodity-to-customer',
                        [
                            'reference_number' => $traderOrder->id,
                            'company_name' => $traderOrder->order->company()->withTrashed()->first()->name,
                            'order_number' => $traderOrder->financing_order_id,
                            'products' => $traderOrder->products,
                            'amount' => $amount,
                            'product_name' => $productName,
                            'customer_name' => $customerName,
                            'contract_signed_date' => $date->tz('Asia/Riyadh')->toDateString(),
                            'contract_signed_time' => $date->tz('Asia/Riyadh')->toTimeString(),
                        ],
                        $traderOrder,
                        TraderOrderMediaCollection::SellingCommodityToCustomer,
                    );
                });
            });
    }
}
