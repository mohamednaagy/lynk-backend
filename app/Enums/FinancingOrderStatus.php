<?php

namespace App\Enums;

use App\Enums\FinancingOrderStatus as Status;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use UnexpectedValueException;

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

    private static array $state = [
        self::InProgress => [
            self::PendingApproval,
        ],
        self::Rejected => [
            self::PendingApproval,
        ],
        self::Completed => [
            self::MurabahaSaleCompleted,
        ],
        self::Canceled => [
            self::Rejected,
            self::InProgress,
            self::RespondedToPtp,
            self::PendingApproval,
            self::CommodityPurchased,
            self::WaitingClientWakala,
            self::PtpDocumentRetrieved,
            self::ClientWakalaCompleted,
            self::CommoditySoldToCustomer,
            self::WaitingPurchasingCommodity,
        ],
        self::WaitingClientWakala => [
            self::InProgress,
        ],
        self::ClientWakalaCompleted => [
            self::WaitingClientWakala,
        ],
        self::WaitingPurchasingCommodity => [
            self::ClientWakalaCompleted,
        ],
        self::RespondedToPtp => [
            self::WaitingPurchasingCommodity,
        ],
        self::PtpDocumentRetrieved => [
            self::RespondedToPtp,
        ],
        self::CommodityPurchased => [
            self::PtpDocumentRetrieved,
        ],
        self::ContractSigned => [
            self::CommodityPurchased,
        ],
        self::CommoditySoldToCustomer => [
            self::ContractSigned,
        ],
        self::MurabhaOfferIssued => [
            self::CommoditySoldToCustomer,
        ],
        self::MurabahaSaleCompleted => [
            self::MurabhaOfferIssued,
        ],
    ];

    /**
     * @param  Status|int  $status
     * @return bool
     */
    public function canMoveTo(Status|int $status): bool
    {
        if (! isset(self::$state[$this->value])) {
            throw new UnexpectedValueException('status not exists');
        }

        if ($status instanceof Status) {
            $status = $status->value;
        }

        return in_array($status, self::$state[$this->value]);
    }

    /**
     * @param  FinancingOrderStatus|int  $status
     * @return bool
     */
    public function cantMoveTo(Status|int $status): bool
    {
        return ! $this->cantMoveTo($status);
    }
}
