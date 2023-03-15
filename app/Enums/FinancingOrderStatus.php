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

    const InProgress = 6;

    const PendingCancellation = 7;

    private static array $state = [
        self::Approved => [
            self::PendingApproval,
        ],
        self::Rejected => [
            self::PendingApproval,
        ],
        self::Completed => [],
        self::Cancelled => [
            self::PendingCancellation,
        ],
        self::PendingCancellation => [
            self::Rejected,
            self::Approved,
            self::PendingApproval,
        ],
    ];

    public static array $requireActionStatuses = [
        self::PendingApproval,
    ];

    public static array $allowedToUpdateStatuses = [
        self::PendingApproval,
        self::Rejected,
    ];

    public static array $nextStep = [
        self::PendingApproval => self::Approved,
    ];

    // Temporary map to handel backend status to end-user status
    public static array $userInterfaceStepsToRealStepsMap = [];

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
