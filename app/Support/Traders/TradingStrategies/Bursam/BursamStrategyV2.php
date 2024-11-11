<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Actions\Contracts\Wakala\GenerateClientWakala;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

class BursamStrategyV2 extends BursamStrategyV1
{
    public static string $version = 'v2';

    public function updatePurchasingCommodity(TraderOrder $traderOrder, array $data)
    {
        parent::updatePurchasingCommodity($traderOrder, $data);
        app(GenerateClientWakala::class)->handle($traderOrder);
    }

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::ContractSigned);

        $this->sellCommodityToCustomer($traderOrder, $request);
    }

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, $request) {}

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, array $data)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::ClientWakala);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::MurabahaSaleCompleted
        );
        $this->createStepHistories(
            $data,
            $traderOrder,
            MurabhaStep::MurabahaSaleCompleted
        );

        if ($canUpdateOrderStatus) {
            $traderOrder->update([
                'status' => TraderOrderStatus::Completed,
            ]);
        }
    }
}
