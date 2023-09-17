<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Http\Request;

class BursamStrategyV2 extends BursamStrategyV1
{
    use TraderHelperTrait;

    public static string $version = 'v2';

    public array $historySteFileMap = [
        FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
            'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
            'file' => 'original_holding_certificate',
        ],
        FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
            'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
            'file' => 'document',
        ],
    ];

    public function updateCommodityCertificateForClient(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::ContractSigned);

        $this->sellCommodityToCustomer($traderOrder, $request);
    }

    public function updateMurabahaPurchaseOffer(TraderOrder $traderOrder, $request)
    {
    }

    public function updateMurabhaCompleteDocument(TraderOrder $traderOrder, Request $request)
    {
        $traderOrder->ensureCanAccessStep(MurabhaStep::ClientWakala);

        $canUpdateOrderStatus = $traderOrder->canChangeParentOrderStatusIfStepWillBeUpdated(
            MurabhaStep::MurabahaSaleCompleted
        );

        $this->createStepHistories(
            $request,
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
