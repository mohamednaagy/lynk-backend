<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\TradingStrategies\Dmcc\Traits\DmccTraderHelperTrait;
use Illuminate\Http\Request;

abstract class BaseDmccStrategy implements TraderStrategyInterface
{
    use DmccTraderHelperTrait;

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
        app(UpdateTraderOrder::class)->handle($traderOrder, $request->validated());

        $trader = Trader::driver($traderOrder->provider, $traderOrder->version);

        $this->createStepHistories(
            $request,
            $trader,
            $traderOrder,
            DmccMurabhaStep::PurchasingCommodity
        );

        $this->transferOwnershipToLender($request, $trader, $traderOrder);
    }

    protected function transferOwnershipToLender($request, $trader, TraderOrder $traderOrder)
    {
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
