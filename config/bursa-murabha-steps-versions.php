<?php

use App\Enums\BursaMurabhaStep;
use App\Enums\FinancingOrderHistory;

return [
    'v1' => [
        BursaMurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        BursaMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::RespondPtp,
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        BursaMurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        BursaMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        BursaMurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        BursaMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
    'v2' => [
        BursaMurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        BursaMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::RespondPtp,
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        BursaMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        BursaMurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        BursaMurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        BursaMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
];
