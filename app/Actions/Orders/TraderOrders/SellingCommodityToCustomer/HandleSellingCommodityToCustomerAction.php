<?php

namespace App\Actions\Orders\TraderOrders\SellingCommodityToCustomer;

use App\Actions\Contracts\Orders\TraderOrders\SellingCommodityToCustomer\HandleSellingCommodityToCustomer;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Exception;
use Illuminate\Http\Request;

class HandleSellingCommodityToCustomerAction implements HandleSellingCommodityToCustomer
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws Exception
     */
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void
    {
        $trader = Trader::driver($traderOrder->drive);

        if ($request->boolean('automatically_generate_file')) {
            $trader->createSellingCommodityToCustomerDocument($traderOrder);
        } else {
            $traderOrder->addMediaFromBase64(
                base64_encode(file_get_contents($request->file('document')))
            )
                ->usingFileName("client-certificate-{$traderOrder->id}.pdf")
                ->toMediaCollection(TraderOrderMediaCollection::SellingCommodityToCustomer);

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateSellingCommodityToCustomerDocument);
        }
    }
}
