<?php

namespace App\Actions\Orders\TraderOrders\PurchasingCommodity;

use App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity\HandlePurchasingCommodity;
use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Illuminate\Http\Request;

class HandlePurchasingCommodityAction implements HandlePurchasingCommodity
{
    use DmccTraderHelperTrait;

    /**
     * @param  Request  $request
     * @param  FinancingOrder  $order
     * @param  TraderOrder  $traderOrder
     * @return void
     *
     * @throws \Exception
     */
    public function handle(
        Request $request,
        FinancingOrder $order,
        TraderOrder $traderOrder
    ): void {
        app(UpdateTraderOrder::class)->handle($traderOrder, $request->validated());

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::PurchasingCommodity
        );

        $this->transferOwnershipToLender($request, $traderOrder);
    }

    protected function transferOwnershipToLender($request, TraderOrder $traderOrder)
    {
        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        // this (if) is a special case doesn't exist in history map
        if ($request->auto_generate_financing_institution_certificate) {
            $trader->createTransferOwnershipToLenderDocument($traderOrder);
        } elseif ($request->has('financing_institution_certificate')) {
            $this->attachDocumentToOrder(
                $traderOrder,
                base64_encode(file_get_contents($request->file('financing_institution_certificate'))),
                TraderOrderMediaCollection::TransferOwnershipToLender,
                'base64'
            );

            $this->createTraderOrderHistory($traderOrder, FinancingOrderHistory::CreateTransferOwnershipToLenderDocument);
        }
    }
}
