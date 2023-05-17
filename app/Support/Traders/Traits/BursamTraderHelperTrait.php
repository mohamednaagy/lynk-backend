<?php

namespace App\Support\Traders\Traits;

use App\Enums\BursamMurabhaStep;
use App\Enums\BursamProductCode;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

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
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ];

    public function getUnusedProductCode()
    {
        $productCodes = BursamProductCode::getValues();
        $unavailableProductCodes = Cache::get('bursam_unavailable_product_codes', []);

        $availableProductCodes = array_diff($productCodes, $unavailableProductCodes);

        return Arr::first(empty($availableProductCodes) ? array_filter($productCodes) : $availableProductCodes);
    }
}
