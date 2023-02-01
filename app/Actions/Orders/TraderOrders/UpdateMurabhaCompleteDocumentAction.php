<?php

namespace App\Actions\Orders\TraderOrders;

use App\Actions\Contracts\Orders\TraderOrders\UpdateMurabhaCompleteDocument;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Exceptions\OrderStatusDoesNotFollowSequenceException;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Illuminate\Http\Request;

class UpdateMurabhaCompleteDocumentAction implements UpdateMurabhaCompleteDocument
{
    use TraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  int  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws OrderStatusDoesNotFollowSequenceException
     */
    public function handle(Request $request, int $order, TraderOrder $traderOrder): void
    {
        $order = FinancingOrder::lockForUpdate()->findOrFail($order);

        if ($order->status->cantMoveTo(FinancingOrderStatus::MurabahaSaleCompleted)) {
            throw new OrderStatusDoesNotFollowSequenceException();
        }

        $trader = Trader::driver($traderOrder->provider);

        $trader->updateOrderStatus($order, FinancingOrderStatus::MurabahaSaleCompleted);

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::MurabahaSaleCompleted
        );

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument
        );

        $this->attachDocumentToOrder(
            $traderOrder,
            base64_encode(file_get_contents($request->file('document'))),
            TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'base64'
        );

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument
        );
    }
}
