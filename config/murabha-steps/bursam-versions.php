<?php

use App\Enums\FinancingOrderHistory;
use App\Enums\MurabhaStep;

return [
    'v1' => [
        MurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        MurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        MurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        MurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        MurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
    'v2' => [
        MurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        MurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        MurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        MurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        MurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        MurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::CommoditySoldToMarket,
            FinancingOrderHistory::GetOwnershipToCustomerCertificate,
            FinancingOrderHistory::GetSellingToMarketCertificate,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
];
