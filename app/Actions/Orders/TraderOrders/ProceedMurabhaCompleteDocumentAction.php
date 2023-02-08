<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\ProceedMurabhaCompleteDocument;
use App\Actions\Contracts\Orders\TraderOrders\UpdateMurabhaCompleteDocument;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Exception;
use Illuminate\Http\Request;

class ProceedMurabhaCompleteDocumentAction implements ProceedMurabhaCompleteDocument
{
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
        $trader = Trader::driver($traderOrder->provider);

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
        );

        app(UpdateMurabhaCompleteDocument::class)->handle($request, $traderOrder);

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
        );

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::MurabahaSaleCompleted
        );

        $trader->updateOrderStatus($order, FinancingOrderStatus::MurabahaSaleCompleted);
    }
}
