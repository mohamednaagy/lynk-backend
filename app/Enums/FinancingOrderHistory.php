<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderHistory extends Enum implements LocalizedEnum
{
    const GetTtiId = 1;

    const CreateSellingCommodityToCustomerDocument = 2;

    const GetPtpDocument = 3;

    const AttachPtpDocumentToOrder = 4;

    const CommodityPurchased = 5;

    const CreateTransferOwnershipToLenderDocument = 6;

    const RespondPtp = 7;

    const GetMurabahaPurchaseOfferDocument = 8;

    const AttachMpoDocument = 9;

    const IssueMurabahaOffer = 10;

    const MurabahaSaleCompleted = 11;

    const GetWarrantAmendmentExceptWarrantNoDocument = 12;

    const ContractSigned = 13;

    const AttachWarrantAmendmentExceptWarrantNoDocument = 14;

    const GetTtiHoldingCertificateDocument = 15;

    const AttachTtiHoldingCertificateDocument = 16;

    const OrderCancelled = 17;

    const ClientWakalaAccepted = 18;

    const Expired = 19;

    public static array $notCancellableActions = [
        self::GetMurabahaPurchaseOfferDocument,
        self::AttachMpoDocument,
        self::IssueMurabahaOffer,
        self::MurabahaSaleCompleted,
        self::GetWarrantAmendmentExceptWarrantNoDocument,
        self::ContractSigned,
        self::AttachWarrantAmendmentExceptWarrantNoDocument,
        self::OrderCancelled,
    ];

    public static array $orderHistoryLastActionMap = [
        FinancingOrderStatus::WaitingPurchasingCommodity => self::GetTtiId,
        FinancingOrderStatus::CommodityPurchased => self::CreateTransferOwnershipToLenderDocument,
        FinancingOrderStatus::ClientWakalaCompleted => self::ClientWakalaAccepted,
        FinancingOrderStatus::MurabhaOfferIssued => self::AttachMpoDocument,
        FinancingOrderStatus::CommoditySoldToCustomer => self::CreateSellingCommodityToCustomerDocument,
        FinancingOrderStatus::MurabahaSaleCompleted => self::MurabahaSaleCompleted,
    ];

    public static array $orderHistoryMapToFinancingOrder = [
        FinancingOrderHistory::GetTtiId => FinancingOrderStatus::WaitingPurchasingCommodity,
        FinancingOrderHistory::RespondPtp => FinancingOrderStatus::RespondedToPtp,
        FinancingOrderHistory::GetPtpDocument => FinancingOrderStatus::PtpDocumentRetrieved,
        FinancingOrderHistory::AttachPtpDocumentToOrder => FinancingOrderStatus::PtpDocumentRetrieved,
        FinancingOrderHistory::GetTtiHoldingCertificateDocument => FinancingOrderStatus::PtpDocumentRetrieved,
        FinancingOrderHistory::AttachTtiHoldingCertificateDocument => FinancingOrderStatus::PtpDocumentRetrieved,
        FinancingOrderHistory::CreateTransferOwnershipToLenderDocument => FinancingOrderStatus::CommodityPurchased,
        FinancingOrderHistory::ContractSigned => FinancingOrderStatus::ContractSigned,
        FinancingOrderHistory::CreateSellingCommodityToCustomerDocument => FinancingOrderStatus::CommoditySoldToCustomer,
        FinancingOrderHistory::ClientWakalaAccepted => FinancingOrderStatus::ClientWakalaCompleted,
        FinancingOrderHistory::IssueMurabahaOffer => FinancingOrderStatus::MurabhaOfferIssued,
        FinancingOrderHistory::GetMurabahaPurchaseOfferDocument => FinancingOrderStatus::MurabhaOfferIssued,
        FinancingOrderHistory::AttachMpoDocument => FinancingOrderStatus::MurabhaOfferIssued,
        FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument => FinancingOrderStatus::MurabahaSaleCompleted,
        FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument => FinancingOrderStatus::MurabahaSaleCompleted,
        FinancingOrderHistory::MurabahaSaleCompleted => FinancingOrderStatus::MurabahaSaleCompleted,
    ];

    public const StepToHistoriesDictionary = [
        FinancingOrderStatus::PendingApproval => [],
        FinancingOrderStatus::Approved => [],
        FinancingOrderStatus::WaitingPurchasingCommodity => [
            FinancingOrderHistory::GetTtiId,
        ],
        FinancingOrderStatus::RespondedToPtp => [
            FinancingOrderHistory::RespondPtp,
        ],
        FinancingOrderStatus::PtpDocumentRetrieved => [
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
        ],
        FinancingOrderStatus::CommodityPurchased => [
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        FinancingOrderStatus::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        FinancingOrderStatus::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        FinancingOrderStatus::WaitingClientWakala => [],
        FinancingOrderStatus::ClientWakalaCompleted => [
            FinancingOrderHistory::ClientWakalaAccepted,
        ],
        FinancingOrderStatus::MurabhaOfferIssued => [
            FinancingOrderHistory::IssueMurabahaOffer,
            FinancingOrderHistory::GetMurabahaPurchaseOfferDocument,
            FinancingOrderHistory::AttachMpoDocument,
        ],
        FinancingOrderStatus::MurabahaSaleCompleted => [
            FinancingOrderHistory::GetWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::AttachWarrantAmendmentExceptWarrantNoDocument,
            FinancingOrderHistory::MurabahaSaleCompleted,
        ],
    ];
}
