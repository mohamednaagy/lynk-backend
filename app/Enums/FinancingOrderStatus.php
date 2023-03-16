<?php

namespace App\Enums;

use App\Enums\FinancingOrderStatus as Status;
use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use UnexpectedValueException;

final class FinancingOrderStatus extends Enum implements LocalizedEnum
{
    const PendingApproval = 1;

    const Approved = 2;

    const Cancelled = 3;

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

    const PendingCancellation = 16;

    const Expired = 17;

    private static array $state = [
        self::Approved => [
            self::PendingApproval,
        ],
        self::Rejected => [
            self::PendingApproval,
        ],
        self::Completed => [
            self::MurabahaSaleCompleted,
        ],
        self::Cancelled => [
            self::PendingCancellation,
        ],
        self::PendingCancellation => [
            self::Rejected,
            self::Approved,
            self::RespondedToPtp,
            self::PendingApproval,
            self::CommodityPurchased,
            self::WaitingClientWakala,
            self::PtpDocumentRetrieved,
            self::ClientWakalaCompleted,
            self::CommoditySoldToCustomer,
            self::WaitingPurchasingCommodity,
        ],
        self::ClientWakalaCompleted => [
            self::WaitingClientWakala,
            self::ContractSigned,
        ],
        self::WaitingPurchasingCommodity => [
            self::Approved,
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
            self::ClientWakalaCompleted,
        ],
        self::WaitingClientWakala => [
            self::ContractSigned,
        ],
        self::MurabahaSaleCompleted => [
            self::MurabhaOfferIssued,
        ],
        self::MurabhaOfferIssued => [
            self::ClientWakalaCompleted,
        ],
    ];

    public static array $requireActionStatuses = [
        self::PendingApproval,
        self::CommodityPurchased,
        self::MurabahaSaleCompleted,
    ];

    public static array $allowedToUpdateStatuses = [
        self::PendingApproval,
        self::Rejected,
    ];

    public static array $nextStep = [
        self::PendingApproval => self::Approved,
        self::Approved => self::WaitingPurchasingCommodity,
        self::WaitingPurchasingCommodity => self::RespondedToPtp,
        self::RespondedToPtp => self::PtpDocumentRetrieved,
        self::PtpDocumentRetrieved => self::CommodityPurchased,
        self::CommodityPurchased => self::ContractSigned,
        self::ContractSigned => self::CommoditySoldToCustomer,
        self::CommoditySoldToCustomer => self::WaitingClientWakala,
        self::WaitingClientWakala => self::ClientWakalaCompleted,
        self::ClientWakalaCompleted => self::MurabhaOfferIssued,
        self::MurabhaOfferIssued => self::MurabahaSaleCompleted,
    ];

    // Temporary map to handel backend status to end-user status
    public static array $userInterfaceStepsToRealStepsMap = [
        self::CommodityPurchased => [
            self::Approved,
            self::WaitingPurchasingCommodity,
            self::RespondedToPtp,
            self::PtpDocumentRetrieved,
            self::CommodityPurchased,
        ],
        self::ContractSigned => [
            self::ContractSigned,
        ],
        self::CommoditySoldToCustomer => [
            self::CommoditySoldToCustomer,
        ],
        self::ClientWakalaCompleted => [
            self::WaitingClientWakala,
            self::ClientWakalaCompleted,
        ],
        self::MurabhaOfferIssued => [
            self::MurabhaOfferIssued,
        ],
        self::MurabahaSaleCompleted => [
            self::MurabahaSaleCompleted,
        ],
    ];

    // Temporary map to handel backend status to end-user status
    public static function getUserInterfaceStep($step): string
    {
        foreach (self::$userInterfaceStepsToRealStepsMap as $uiStep => $realSteps) {
            if (in_array($step, $realSteps)) {
                return FinancingOrderStatus::fromValue($uiStep)->description;
            }
        }

        return FinancingOrderStatus::fromValue($step)->description;
    }

    /**
     * @param  Status|int  $status
     * @return bool
     */
    public function canMoveTo(Status|int $status): bool
    {
        if ($status instanceof Status) {
            $status = $status->value;
        }

        if (! isset(self::$state[$status])) {
            throw new UnexpectedValueException('no mapping for this status');
        }

        return in_array($this->value, self::$state[$status]);
    }

    /**
     * @param  FinancingOrderStatus|int  $status
     * @return bool
     */
    public function cantMoveTo(Status|int $status): bool
    {
        return ! $this->canMoveTo($status);
    }

    /**
     * @return bool
     */
    public function canBeUpdated(): bool
    {
        return in_array($this->value, self::$allowedToUpdateStatuses);
    }

    /**.
     * @return bool
     */
    public function cantBeUpdated(): bool
    {
        return ! $this->canBeUpdated();
    }
}
