<?php

namespace App\Services\LocalMarket;

use App\Models\LocalMarketOrder;
use App\Services\Traits\WithAutocommitDisabledTrait;
use Illuminate\Support\Facades\Log;

class LoanService
{
    use WithAutocommitDisabledTrait;

    private $orderService;

    private $unitService;

    private $inventoryService;

    public function __construct()
    {
        $this->orderService = app(OrderService::class);
        $this->unitService = app(UnitService::class);
        $this->inventoryService = app(InventoryService::class);
    }

    public function getCommoditiesForLoan(LocalMarketOrder $localMarketOrder)
    {
        $startTime = microtime(true);

        $eligibleInventories = $this->inventoryService->findEligibleInventoryForLoan(
            $localMarketOrder
        );

        if (empty($eligibleInventories)) {
            return false;
        }

        Log::channel('local_market')->info('getCommoditiesForLoan Duration', [
            'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            'order_id' => $localMarketOrder->id,
        ]);

        return $this->unitService->getEligibleUnits($localMarketOrder, $eligibleInventories);
    }

    public function sellCommodities(LocalMarketOrder $localMarketOrder)
    {

        /**
         * TODO:
         * 1- increase units rotations for the company
         * 2- change units ownershop
         * 3- remove hold for from units
         * 4- change unit status to available
         * 4- change order status to commodities sold
         * 5- log the action
         * 6- send notification to the company
         */
        Log::info('We wll sell your commodities ISA soon', ['order_id' => $localMarketOrder->id]);
    }
}
