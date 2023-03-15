<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MurabhaStep extends Enum
{
    const PurchasingCommodity = 1;

    const ContractSigned = 2;

    const CommoditySoldToCustomer = 3;

    const ClientWakala = 4;

    const MurabhaOfferIssued = 5;

    const MurabahaSaleCompleted = 6;

    const StepToHistoriesDictionary = [
        self::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiId,
            FinancingOrderHistory::RespondPtp,
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        self::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        self::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        self::ClientWakala => [
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        self::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        self::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ];
}
