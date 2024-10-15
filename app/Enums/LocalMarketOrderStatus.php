<?php

namespace App\Enums;

use App\Enums\FinancingOrderStatus as Status;
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

    const pendingCancellation = 6;

    const Cancelled = 7;

    const Completed = 8;

    const PendingSellCommodities = 9;

    const CommoditiesSell = 10;

    const FailedSell = 11;

    private static array $state = [
        self::initiate => [
            self::PendingEligibleCommodities,
        ],
        self::PendingEligibleCommodities => [
            self::EligibleCommoditiesAvailable,
            self::NoEligibleCommoditiesAvailable,
            self::FailedPurchase,
        ],
        self::Completed => [],
        self::NoEligibleCommoditiesAvailable => [
            self::FailedPurchase,
            self::Cancelled,
        ],
        self::EligibleCommoditiesAvailable => [
            self::CommoditiesPurchased,
            self::FailedPurchase,
        ],
        self::CommoditiesPurchased => [
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
