<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\OwnershipService;
use App\Services\UnitService;
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
        $loanDetails = [
            'inventories' => [],
            'isLoanCovered' => false,
            'remainingLoan' => $loanAmount,
            'numberOfSuitableUnits' => 0,
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
                break;
            }

            $eligibleUnits = $unitsService->getEligibleUnits($orderNo, $companyId, $inventory, $loanDetails['remainingLoan']);

            $usedInventories[] = $inventory->id;
            $loanDetails['inventories'][] = $eligibleUnits;
            $loanDetails['isLoanCovered'] = $eligibleUnits['isLoanCovered'];
            $loanDetails['remainingLoan'] = $eligibleUnits['remainingLoan'];
            $loanDetails['numberOfSuitableUnits'] = +$eligibleUnits['numberOfSuitableUnits'];
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
            // try to save trader order ownership
            // $ownershipService->changeUnitOwnership($localMarketOrder, $eligibleCommodities->getNumberOfSuitableUnits(), OwnershipTypes::Company, $companyId);
            $inventoryService->refreshInventoryStocks($eligibleCommodities->getInventoriesIds());
            // $orderService->insertOrderUnits($localMarketOrder, $units);
            $orderService->insertOrderInventories($localMarketOrder, $eligibleCommodities->getInventories());
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::CommoditiesPurchased);
            dd('inventories done ');

            DB::commit();

            Log::info('Commodities bought successfully');
        } catch (Exception $e) {
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::FailedPurchase);

            DB::rollBack();
        }
    }
}
