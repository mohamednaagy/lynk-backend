<?php

namespace App\Http\Controllers\Api\V1\Lender\Orders;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Http\Controllers\Controller;
use App\Jobs\UpdateFinancialOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Http\JsonResponse;

class ContractSigned extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  FinancingOrder  $order
     * @return JsonResponse
     */
    public function __invoke(FinancingOrder $order): JsonResponse
    {
        $order->update([
            'status' => FinancingOrderStatus::ContractSigned,
        ]);

        $traderOrder = $order->traderOrders->last();
        UpdateFinancialOrderStatus::dispatch($traderOrder, FinancingOrderStatus::SellingCommodityToCustomer)->delay(now()->addMinutes(2));

        Trader::driver('dmcc')->createTraderOrderHistory($traderOrder, FinancingOrderHistory::ResponsePtp);

        return $this->successResponse();
    }
}
