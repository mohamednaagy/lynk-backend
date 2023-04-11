<?php

namespace App\Support\Traders\TradingStrategies\Dmcc;

use App\Actions\Contracts\Orders\UpdateTraderOrder;
use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use App\Support\Traders\TradingStrategies\Contracts\TraderStrategyInterface;
use App\Support\Traders\Traits\DmccTraderHelperTrait;
use Illuminate\Http\Request;

abstract class BaseDmccStrategy implements TraderStrategyInterface
{
    use DmccTraderHelperTrait;

    public function updatePurchasingCommodity(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::TraderOrderCreated);

        app(UpdateTraderOrder::class)->handle($traderOrder, $request->validated());

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::PurchasingCommodity
        );

        $this->transferOwnershipToLender($traderOrder, $request);
    }

    protected function transferOwnershipToLender(TraderOrder $traderOrder, $request)
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

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(DmccMurabhaStep::ClientWakala);

        $this->createStepHistories(
            $request,
            $traderOrder,
            DmccMurabhaStep::MurabhaOfferIssued
        );
    }
}
