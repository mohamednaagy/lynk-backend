<?php

namespace App\Support\Traders\Traits;

use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use UnexpectedValueException;

trait LocalMarketHelperTrait
{
    public function createLocalMarketOrderHistory(LocalMarketOrder $order, int $status): void
    {
        $order->histories()->updateOrCreate(
            [
                'status' => $status,
            ]
        );
    }

    public function canMoveToNextStep($currentStep, int $nextStep): bool
    {
        $currentStep = LocalMarketOrderStatus::getEnumInstanceByValue($currentStep);
        $nextStep = LocalMarketOrderStatus::getEnumInstanceByValue($nextStep);
        $checkStep = $currentStep->canMoveTo($nextStep->value);
        if (! $currentStep->canMoveTo($nextStep->value)) {
            throw new UnexpectedValueException('please make sure from your step');
        }

        return $checkStep;
    }
}
