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
        $groupedDataByPreviousOwner = $order->data['data'];
        $inventories = $order->data['inventories'];
        foreach ($groupedDataByPreviousOwner as $key => $data) {
            $inventory = $inventories[$data['local_market_inventory_id']];
            $newData['products'][$key]['currency'] = $inventory['currency']['name'];
            $newData['products'][$key]['uom'] = $inventory['measurement']['name'];
            $newData['products'][$key]['type'] = $inventory['commodityType']['name'];
            $newData['products'][$key]['amount'] = $data['unit_count'] * $inventory['price'];
            $newData['products'][$key]['product'] = $inventory['item']['name'];
            $newData['products'][$key]['location'] = $inventory['location']['name'];
            $newData['products'][$key]['previous_owner'] = $data['previous_owner'];
            $newData['products'][$key]['original_supplier'] = $inventory['supplier']['name'];
            $newData['products'][$key]['quantity'] = $data['unit_count'] * $inventory['item']['volume_sellable_unit'];
        }

        return $newData;
    }
}
