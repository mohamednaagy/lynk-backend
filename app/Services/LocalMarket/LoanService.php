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
        int $companyId,
        float $loanAmount,
        array $preferredTypes = [],
        array $loanInventories = []
    ): array {
        $loanDetails = [
            'inventories_id' => [],
            'inventories' => [],
            'isLoanCovered' => false,
            'remainingLoan' => $loanAmount,
        ];

        $inventoryService = app(InventoryService::class);
        $unitsService = app(UnitService::class);

        while (! $loanDetails['isLoanCovered'] && $loanDetails['remainingLoan'] > 0) {
            $inventory = $inventoryService->findEligibleInventoryForLoan(
                $loanDetails['remainingLoan'],
                $preferredTypes,
                array_merge($loanInventories, $loanDetails['inventories_id'])
            );

            if (! $inventory) {
                break;
            }

            $eligibleUnits = $unitsService->getEligibleUnits($companyId, $inventory, $loanDetails['remainingLoan']);

            $loanDetails['inventories_id'][] = $inventory->id;
            $loanDetails['inventories'][] = $eligibleUnits;
            $loanDetails['isLoanCovered'] = $eligibleUnits['isLoanCovered'];
            $loanDetails['remainingLoan'] = $eligibleUnits['remainingLoan'];
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
        $units = $eligibleCommodities->getAllUnits();

        try {
            $unitService->changeUnitStatus($units, InventoryUnitsStatus::Reserved);
            // TODO try to save trader order ownership
            $ownershipService->changeUnitOwnership($units, OwnershipTypes::Company, $companyId);
            $inventoryService->refreshInventoryStocks($eligibleCommodities->getInventoriesIds());
            $orderService->insertOrderUnits($localMarketOrder, $units);
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::CommoditiesPurchased);
            DB::commit();

            Log::info('Commodities bought successfully');
        } catch (Exception $e) {
            Log::error('Error in buy commodities', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::FailedPurchase);

            DB::rollBack();
        }
    }
}
