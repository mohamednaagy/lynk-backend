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
                break;
            }

            $eligibleUnits = $unitsService->getEligibleUnits($orderNo, $companyId, $inventory, $loanDetails['remainingLoan'], $maxNumberOfUnits);

            $usedInventories[] = $inventory->id;
            $loanDetails['inventories'][] = $eligibleUnits;
            $loanDetails['isLoanCovered'] = $eligibleUnits['isLoanCovered'];
            $loanDetails['remainingLoan'] = $eligibleUnits['remainingLoan'];
            $loanDetails['purchasingFailureReason'] = $eligibleUnits['failureReason'];
            $loanDetails['numberOfSuitableUnits'] = +$eligibleUnits['numberOfSuitableUnits'];

            $maxNumberOfUnits = -$eligibleUnits['numberOfSuitableUnits'];
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
        } catch (Exception $e) {
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::FailedPurchase);

            DB::rollBack();
        }
    }
}
