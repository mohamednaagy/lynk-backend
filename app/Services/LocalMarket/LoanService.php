<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\DataTransferObjects\LocalMarket\OrderCommoditiesDto;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanService
{
    public function getCommoditiesForLoan(
        int $orderNo,
        int $companyId,
        float $loanAmount,
        array $preferredTypes = []
    ): array {
        $maxNumberOfUnits = 10000;

        $loanDetails = [
            'inventories' => [],
            'isLoanCovered' => false,
            'remainingLoan' => $loanAmount,
            'numberOfSuitableUnits' => 0,
            'purchasingFailureReason' => '',
        ];

        $inventoryService = app(InventoryService::class);
        $unitsService = app(UnitService::class);
        $usedInventories = [];

        while (! $loanDetails['isLoanCovered'] && $loanDetails['remainingLoan'] > 0) {
            $inventory = $inventoryService->findEligibleInventoryForLoan(
                $loanDetails['remainingLoan'],
                $preferredTypes,
                $usedInventories
            );

            if (! $inventory) {
                Log::info('There is no valid inventory for ', [
                    'loanAmount' => $loanAmount,
                    'preferredItemTypes' => $preferredTypes,
                    'usedInventories' => $usedInventories,
                ]);
                $loanDetails['purchasingFailureReason'] = 'there is no valid inventory';

                return $loanDetails;
            }

            $eligibleUnits = $unitsService->getEligibleUnits($orderNo, $companyId, $inventory, $loanDetails['remainingLoan'], $maxNumberOfUnits);

            $usedInventories[] = $inventory->id;
            $loanDetails['inventories'][] = $eligibleUnits;
            $loanDetails['isLoanCovered'] = $eligibleUnits['isLoanCovered'];
            $loanDetails['remainingLoan'] = $eligibleUnits['remainingLoan'];
            $loanDetails['purchasingFailureReason'] = $eligibleUnits['failureReason'];
            $loanDetails['numberOfSuitableUnits'] = $loanDetails['numberOfSuitableUnits'] + $eligibleUnits['numberOfSuitableUnits'];
            $maxNumberOfUnits = $maxNumberOfUnits - $eligibleUnits['numberOfSuitableUnits'];
        }

        return $loanDetails;
    }

    public function buyCommodities(LocalMarketOrder $localMarketOrder, $companyId, $data)
    {
        DB::beginTransaction();
        $unitService = new UnitService;
        $ownershipService = new OwnershipService;
        $inventoryService = new InventoryService;
        $orderService = new OrderService;

        $eligibleCommodities = OrderCommoditiesDto::fromArray($data);

        try {
            $unitService->changeUnitStatus($localMarketOrder, InventoryUnitsStatus::Reserved);

            $ownershipService->changeUnitOwnership($localMarketOrder, OwnershipTypes::Company, $companyId);
            $inventoryService->refreshInventoryStocks($eligibleCommodities->getInventoriesIds());
            $orderService->insertOrderUnits($localMarketOrder);
            $orderService->insertOrderInventories($localMarketOrder, $eligibleCommodities->getInventories());
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::CommoditiesPurchased);

            DB::commit();

            return true;
        } catch (Exception $e) {
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();

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
