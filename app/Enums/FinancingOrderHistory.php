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

    const ResponsePtp = 7;

    const IssueMurabahaPurchaseOffer = 8;

    const GetMurabahaPurchaseOfferDocument = 9;

    const AttachMpoDocument = 10;

    const IssueMurabahaOffer = 11;

    const MurabahaSaleCompleted = 12;

    const GetWarrantAmendmentExceptWarrantNoDocument = 13;
}
