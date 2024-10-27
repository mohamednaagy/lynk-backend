<?php

namespace App\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum;
use UnexpectedValueException;

final class LocalMarketOrderStatus extends Enum implements LocalizedEnum
{
    const initiate = 0;

    const PendingEligibleCommodities = 1;

    const CommoditiesPurchased = 2;

    const EligibleCommoditiesAvailable = 3;

    const NoEligibleCommoditiesAvailable = 4;

    const FailedPurchase = 5;

    const PendingCancellation = 6;

    const Cancelled = 7;

    const Completed = 8;

    const PendingSellCommodities = 9;

    const CommoditiesSell = 10;

    const FailedSell = 11;

    const FailedToCancel = 12;

    const TransferOwnershipToCustomer = 13;

    private static array $state = [
        self::initiate => [
            self::PendingEligibleCommodities,
            self::PendingCancellation,

        ],
        self::PendingEligibleCommodities => [
            self::EligibleCommoditiesAvailable,
            self::NoEligibleCommoditiesAvailable,
            self::FailedPurchase,
            self::PendingCancellation,
        ],
        self::Completed => [],
        self::NoEligibleCommoditiesAvailable => [
            self::FailedPurchase,
            self::PendingCancellation,
        ],
        self::EligibleCommoditiesAvailable => [
            self::CommoditiesPurchased,
            self::PendingCancellation,
        ],
        self::CommoditiesPurchased => [
            self::PendingCancellation,
            self::PendingSellCommodities,
            self::TransferOwnershipToCustomer,

        ],
        self::PendingCancellation => [
            self::Cancelled,
            self::FailedToCancel,
        ],

        self::PendingSellCommodities => [
            self::CommoditiesSell,
        ],
        self::Cancelled => [
            self::FailedToCancel,
        ],

        self::CommoditiesSell => [],
        self::TransferOwnershipToCustomer => [
            self::PendingSellCommodities,
        ],

    ];

    public function canMoveTo(int $status): bool
    {
        return in_array($status, self::$state[$this->value]);
    }

    /**
     * Get the key name for a given status value.
     */
    public static function getEnumInstanceByValue(int $value): self
    {
        $constants = (new \ReflectionClass(self::class))->getConstants();
        $key = array_search($value, $constants, true);

        if ($key === false) {
            throw new UnexpectedValueException("No key found for value $value");
        }

        return new self($constants[$key]);
    }
}
