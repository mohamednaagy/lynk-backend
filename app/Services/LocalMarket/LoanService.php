<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarket\UnitOwnershipAction;
use App\Models\LocalMarketOrder;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService
{
    public function getCommoditiesForLoan(LocalMarketOrder $localMarketOrder)
    {
        $startTime = microtime(true);
        $inventoryService = app(InventoryService::class);
        $unitsService = app(UnitService::class);
        $eligibleInventories = [];
        $eligibleUnits = [];

        // Use a transaction to ensure data integrity
        $eligibleUnits = DB::transaction(function () use ($localMarketOrder, $inventoryService, $unitsService, &$eligibleInventories) {
            $eligibleInventories = $inventoryService->findEligibleInventoryForLoan($localMarketOrder);

            // Early exit if no eligible inventories are found
            if (empty($eligibleInventories)) {
                return false;
            }

            return $unitsService->getEligibleUnits($localMarketOrder, $eligibleInventories);
        }, 1, 'SERIALIZABLE');

        Log::channel('local_market')->info('Get Commodities For Loan Transaction Duration', [
            'order_id' => $localMarketOrder->id,
            'duration' => convertMicrotimeToDuration(microtime(true) - $startTime),
            'eligibleInventoriesCount' => count($eligibleInventories),
        ]);

        return $eligibleUnits;
    }

    public function buyCommodities(LocalMarketOrder $localMarketOrder)
    {
        $orderService = new OrderService;
        $unitService = new UnitService;

        DB::beginTransaction();
        try {
            $unitService->changeOrderUnitsOwnershipTo($localMarketOrder, OwnershipTypes::Company, $localMarketOrder->company_id, UnitOwnershipAction::PurchaseCommodity);
            $orderService->insertOrderUnits($localMarketOrder);
            $orderService->insertOrderInventories($localMarketOrder);
            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return false;
        }
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
