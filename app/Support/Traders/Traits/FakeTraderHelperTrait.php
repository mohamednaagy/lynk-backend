<?php

namespace App\Support\Traders\Traits;

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;

trait FakeTraderHelperTrait
{
    use TraderHelperTrait;

    public array $stepToHistoriesMap = [
        DmccMurabhaStep::PurchasingCommodity => [
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
        DmccMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala => null,
            FinancingOrderHistory::ClientWakalaAccepted => null,
        ],
        DmccMurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer => null,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument => null,
            FinancingOrderHistory::AttachMpoDocument => [
                'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'file' => 'document',
            ],
        ],
        DmccMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
                'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'file' => 'document',
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ];
}
