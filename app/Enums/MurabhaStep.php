<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class MurabhaStep extends Enum
{
    const PurchasingCommodity = 'purchasing_commodity';

    const CommodityPurchased = 'commodity_purchased';

    const ContractSigned = 'contract_signed';

    const CommoditySoldToCustomer = 'commodity_sold_to_customer';

    const WaitingClientWakala = 'waiting_client_wakala';

    const ClientWakalaCompleted = 'client_wakala_completed';

    const MurabhaOfferIssued = 'murabha_offer_issued';

    const MurabahaSaleCompleted = 'murabaha_sale_completed';

    public static array $stepToHistoriesDictionary = [
        self::PurchasingCommodity => [
            FinancingOrderHistory::GetTtiId,
            FinancingOrderHistory::RespondPtp,
            FinancingOrderHistory::GetPtpDocument,
            FinancingOrderHistory::AttachPtpDocumentToOrder,
            FinancingOrderHistory::GetTtiHoldingCertificateDocument,
            FinancingOrderHistory::AttachTtiHoldingCertificateDocument,
            FinancingOrderHistory::CreateTransferOwnershipToLenderDocument,
        ],
        self::CommodityPurchased => [
            FinancingOrderHistory::CommodityPurchased,
        ],
        self::ContractSigned => [
            FinancingOrderHistory::ContractSigned,
        ],
        self::CommoditySoldToCustomer => [
            FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
        ],
        self::WaitingClientWakala => [
            FinancingOrderHistory::WaitingClientWakala,
        ],
        self::ClientWakalaCompleted => [
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
