<?php

use App\Enums\DmccMurabhaStep;
use App\Enums\FinancingOrderHistory;

return [
    'v1' => [
        DmccMurabhaStep::TraderOrderCreated => [
            FinancingOrderHistory::GetTtiId,
        ],
        DmccMurabhaStep::PurchasingCommodity => [
            FinancingOrderHistory::RespondPtp,
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        DmccMurabhaStep::ClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        DmccMurabhaStep::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        DmccMurabhaStep::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        DmccMurabhaStep::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        DmccMurabhaStep::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ],
];
