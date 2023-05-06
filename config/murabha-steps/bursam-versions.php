<?php

use App\Enums\BursamMurabhaStep;
use App\Enums\FinancingOrderHistory;

return [
    'v1' => [
        BursamMurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        BursamMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        BursamMurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        BursamMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        BursamMurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        BursamMurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        BursamMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
    'v2' => [
        BursamMurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        BursamMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
        ],
        BursamMurabhaStep::TransferOwnershipToLender => [
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        BursamMurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        BursamMurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        BursamMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        BursamMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
];
