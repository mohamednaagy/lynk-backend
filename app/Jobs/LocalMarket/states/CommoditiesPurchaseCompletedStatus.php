<?php

namespace App\Jobs\LocalMarket\states;

use App\Actions\Contracts\LocalMarket\BuyCommodities;
use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Models\LocalMarketOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CommoditiesPurchaseCompletedStatus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private LocalMarketOrder $localMarketOrder)
    {
        $this->onQueue('local_market');
    }

    /**
     * Execute the job.
     */
    public function handle(BuyCommodities $buyCommodities): void
    {
        $data = [
            'external_order_no' => 'O7F5ZL81CYOMY81711',
            'case' => 'CommoditiesPurchased',
            'products' => [
                'uom' => 'Delectus nulla cupi',
                'type' => 'Ea fuga Ad rem et n',
                'amount' => '22',
                'product' => 'Laboriosam numquam',
                'currency' => 'Fugiat consequatur',
                'location' => 'Accusantium sequi ar',
                'quantity' => '787',
                'previous_owner' => 'Dolore perspiciatis',
                'original_supplier' => 'Accusamus sunt quos',
            ],
        ];
        app(LocalMarketWebhook::class)->handle($data);
        // we will notify the owner we are done buying
        // nagy will handle it
        // send post request to lynk with commadites DTO
        // {
        // "CASE" : "PURCHASED_COMMODITIES",
        // "CASE" : "SELLED_COMMODITIES",
        // "CASE" : "CAncel",
        // "CASE" : "PURCHASING_FAILURE",
        // "CASE" : "SELLING_FAILURE",
        // "data": {
        //                "products": [
        //                  {
        //                    "uom": "Delectus nulla cupi",
        //                    "type": "Ea fuga Ad rem et n",
        //                    "amount": "22",
        //                    "product": "Laboriosam numquam",
        //                    "currency": "Fugiat consequatur",
        //                    "location": "Accusantium sequi ar",
        //                    "quantity": "787",
        //                    "previous_owner": "Dolore perspiciatis",
        //                    "original_supplier": "Accusamus sunt quos"
        //                  }
        //                ]
        //          }
        // }

        Log::info("Congratulations Commodities purchased for order {$this->localMarketOrder->id}");

    }
}
