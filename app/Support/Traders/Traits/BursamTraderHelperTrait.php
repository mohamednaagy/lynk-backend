<?php

namespace App\Support\Traders\Traits;

use App\Enums\BursamMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;

trait BursamTraderHelperTrait
{
    use TraderHelperTrait;

    public array $stepToHistoriesMap = [
        BursamMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                'file' => 'original_holding_certificate',
            ],
        ],
        BursamMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala => null,
            FinancingOrderHistory::ClientWakalaAccepted => null,
        ],
        BursamMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetSellingToBursaCertificate => null,
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ];
}
