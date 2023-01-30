<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use Illuminate\Support\Collection;

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

    public static function isTraderOrderCancellable(FinancingOrderHistory|Collection|array $actions): bool
    {
        if ($actions instanceof FinancingOrderHistory) {
            $actions = (array) $actions->value;
        }

        if ($actions instanceof Collection) {
            $actions = $actions->toArray();
        }

        return ! count(array_intersect(FinancingOrderHistory::$notCancellableActions, $actions));
    }
}
