<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const PendingApproval = 1;

    const InProgress = 2;

    const Canceled = 3;

    const Completed = 4;

    const Rejected = 5;

    const CommodityPurchased = 6;

    const CommoditySoldToCustomer = 7;

    const MurabhaOfferIssued = 8;

    const MurabahaSaleCompleted = 9;

    const ContractSigned = 10;

    const WaitingClientWakala = 11;

    const WaitingPurchasingCommodity = 12;

    const ClientWakalaCompleted = 13;

    const RespondedToPtp = 14;

    const PtpDocumentRetrieved = 15;

    const PendingCancel = 15;
}
