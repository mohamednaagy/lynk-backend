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
            throw new UnexpectedValueException("can not move $currentStep to $nextStep");
        }

        return $checkStep;
    }

    public function getDataOfLocalMarketOrder(LocalMarketOrder $order): array
    {
        $newData['external_order_no'] = $order->external_order_no;
        foreach ($order->data['inventories'] as $key => $data) {
            $newData['products'][$key]['currency'] = $data['currency']['name'];
            $newData['products'][$key]['uom'] = $data['measurement']['name'];
            $newData['products'][$key]['type'] = $data['commodityType']['name'];
            $newData['products'][$key]['amount'] = $data['totalCost'];
            $newData['products'][$key]['product'] = $data['item']['name'];
            $newData['products'][$key]['location'] = $data['location']['name'];
            $newData['products'][$key]['previous_owner'] = $data['supplier']['name'];
            $newData['products'][$key]['original_supplier'] = $data['supplier']['name'];
            $newData['products'][$key]['quantity'] = $data['numberOfSuitableUnits'] * $data['item']['volume_sellable_unit'];
        }

        return $newData;
    }
}
