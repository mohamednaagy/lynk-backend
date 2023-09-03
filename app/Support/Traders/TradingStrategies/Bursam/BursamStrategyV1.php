<?php

namespace App\Support\Traders\TradingStrategies\Bursam;

use App\Enums\BursamMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Support\Traders\Traits\TraderHelperTrait;

class BursamStrategyV1 extends BaseBursamStrategy
{
    use TraderHelperTrait;

    public array $stepToHistoriesMap = [
        BursamMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::RespondPtp => null,
            FinancingOrderHistory::GetPtpDocument => null,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
            FinancingOrderHistory::AttachPtpDocumentToOrder => [
                'collection' => TraderOrderMediaCollection::PromiseToPurchase,
                'file' => 'ptp_document',
            ],
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                'file' => 'original_holding_certificate',
            ],
        ],
        BursamMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala => null,
            FinancingOrderHistory::ClientWakalaAccepted => null,
        ],
        BursamMurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer => null,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument => null,
            FinancingOrderHistory::AttachMpoDocument => [
                'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'file' => 'document',
            ],
        ],
        BursamMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
                'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'file' => 'document',
            ],
            FinancingOrderHistory::CommoditySoldToMarket => null,
            FinancingOrderHistory::GetOwnershipToCustomerCertificate => null,
            FinancingOrderHistory::GetSellingToMarketCertificate => null,
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ];
}
