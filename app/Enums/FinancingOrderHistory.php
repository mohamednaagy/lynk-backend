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
}
