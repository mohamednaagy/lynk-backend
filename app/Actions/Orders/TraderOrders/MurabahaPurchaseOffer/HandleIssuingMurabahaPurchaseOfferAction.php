<?php

namespace App\Actions\Orders\TraderOrders\MurabahaPurchaseOffer;

use App\Actions\Contracts\Orders\TraderOrders\MurabahaPurchaseOffer\HandleIssuingMurabahaPurchaseOffer;
use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TraderHelperTrait;
use Exception;
use Illuminate\Http\Request;

class HandleIssuingMurabahaPurchaseOfferAction implements HandleIssuingMurabahaPurchaseOffer
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
        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            FinancingOrderStatus::MurabhaOfferIssued
        );

        $trader = Trader::driver($traderOrder->provider);

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::IssueMurabahaOffer
        );

        if ($canUpdateOrderStatus) {
            $trader->updateOrderStatus($order, FinancingOrderStatus::MurabhaOfferIssued);
        }

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument
        );

        $this->attachDocumentToOrder(
            $traderOrder,
            base64_encode(file_get_contents($request->file('document'))),
            TraderOrderMediaCollection::MurabahaPurchaseOrder,
            'base64'
        );

        $trader->createTraderOrderHistory(
            $traderOrder,
            FinancingOrderHistory::AttachMpoDocument
        );
    }
}
