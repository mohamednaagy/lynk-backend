<?php

use App\Enums\FinancingOrderHistory;
use App\Enums\MediaCollections\TraderOrderMediaCollection;
use App\Enums\MurabhaStep;

return [
    'v1' => [
        MurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId => null,
        ],
        MurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                'file' => 'original_holding_certificate',
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
        ],
        MurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned => null,
        ],
        MurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala => null,
            FinancingOrderHistory::ClientWakalaAccepted => null,
        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
        ],
        MurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer => null,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument => null,
            FinancingOrderHistory::AttachMpoDocument => [
                'collection' => TraderOrderMediaCollection::MurabahaPurchaseOrder,
                'file' => 'document',
            ],
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => [
                'collection' => TraderOrderMediaCollection::WarrantAmendmentExceptWarrantNo,
                'file' => 'document',
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ],
    'v2' => [
        MurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId => null,
        ],
        MurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument => null,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument => [
                'collection' => TraderOrderMediaCollection::TtiHoldingCertificate,
                'file' => 'original_holding_certificate',
            ],
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => null,
        ],
        MurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned => null,
        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => null,
        ],
        MurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala => null,
            FinancingOrderHistory::ClientWakalaAccepted => null,
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => null,
            FinancingOrderHistory::CommoditySoldToMarket => null,
            FinancingOrderHistory::GetOwnershipToCustomerCertificate => null,
            FinancingOrderHistory::GetSellingToMarketCertificate => [
                'collection' => TraderOrderMediaCollection::BursamTtiHoldingCertificate,
                'file' => 'document',
            ],
            FinancingOrderHistory::MurabahaSaleCompleted => null,
        ],
    ],
];
