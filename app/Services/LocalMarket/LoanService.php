<?php

namespace App\Services\LocalMarket;

use App\Enums\LocalMarket\InventoryStatus;
use App\Enums\LocalMarket\InventoryUnitsStatus;
use App\Enums\LocalMarket\OwnershipTypes;
use App\Enums\LocalMarketOrderStatus;
use App\Enums\Trader;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketInventoryUnits;
use App\Models\LocalMarketOrder;
use App\Models\LocalMarketOrderHasInventory;
use App\Models\LocalMarketUnitOwnership;
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
    public function getCommoditiesForLoan($companyId, $loanAmount, $preferredTypes = [], $loanInventories = [])
    {
        $loanDetails = [
            'inventories_id' => [],
            'inventories' => [],
            'isLoanCovered' => false,
        ];

        $inventoryService = new InventoryService;
        $unitsService = new UnitService;

        $inventory = $inventoryService->findEligibleInventoryForLoan($loanAmount, $preferredTypes = [], $loanInventories);

        if (empty($inventory)) {
            Log::alert('No eligible inventory found for loan', ['company_id' => $companyId, 'loan_amount' => $loanAmount, 'preferred_types' => $preferredTypes]);

            return $loanDetails;
        }

        $loanDetails['inventories_id'][] = $inventory->id;
        $eligibleUnits = $unitsService->getEligibleUnits($companyId, $inventory, $loanAmount);

        $loanDetails['inventories'][] = $eligibleUnits;
        $loanDetails['isLoanCovered'] = $eligibleUnits['isLoanCovered'];
        $loanDetails['remainingLoan'] = $eligibleUnits['remainingLoan'];

        if ($loanDetails['isLoanCovered']) {
            return $loanDetails;
        }

        // Recursive call to handle remaining loan amount
        $this->getCommoditiesForLoan($companyId, $loanDetails['remainingLoan'], $preferredTypes, $loanDetails['inventories_id']);
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
            $ownershipService->changeUnitOwnership($units, OwnershipTypes::Company, $companyId);
            $inventoryService->refreshInventoryStocks($eligibleCommodities->getInventoriesIds());
            $orderService->insertOrderUnits($localMarketOrder, $units);
            $orderService->changeOrderStatus($localMarketOrder, LocalMarketOrderStatus::BuyCommoditiesDone);

            // recalculate free and reserved items
            DB::commit();

            Log::info('Commodities bought successfully');
        } catch (Exception $e) {
            Log::error('Error in buy commoadites', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
        }
    }

    /**
     * Retrieves an inventory item from the local market based on the loan amount, preferred item types, and used inventories.
     */
    public function findEligibleInventory(int $loanAmount, array $preferredItemTypes = [], array $usedInventories = []): ?LocalMarketInventory
    {
        return LocalMarketInventory::where('max_price', '<=', $loanAmount)
            ->where('status', InventoryStatus::Active)
            ->when(! empty($preferredItemTypes), function ($query) use ($preferredItemTypes) {
                $query->whereIn('commodity_type_id', $preferredItemTypes);
            })
            ->when(! empty($usedInventories), function ($query) use ($usedInventories) {
                $query->whereNotIn('id', $usedInventories);
            })
            ->orderByRaw('(`available_quantity` * `max_price`) DESC')
            ->first();
    }

    /**
     * Get suitable units from inventory based on the loan amount.
     */
    public function getSuitableUnits(int $companyId, LocalMarketInventory $inventory, float $loan): array
    {
        $numberOfNeededUnits = $this->calculateNumberOfNeededUnits($loan, $inventory->price());

        $availableUnits = $this->extractValidUnitsForCompany($inventory, $companyId, $numberOfNeededUnits);

        $totalAvailableUnitsCost = count($availableUnits) * $inventory->price();
        $remainingLoan = $loan - $totalAvailableUnitsCost;

        return [
            'inventoryId' => $inventory->id,
            'numberOfNeededUnits' => $numberOfNeededUnits,
            'numberOfSuitableUnits' => count($availableUnits),
            'availableUnits' => $availableUnits,
            'totalCost' => $totalAvailableUnitsCost,
            'remainingLoan' => $remainingLoan,
            'isLoanCovered' => ($remainingLoan == 0),
        ];
    }

    /**
     * Extracts valid units for a specific company from the inventory.
     */
    private function extractValidUnitsForCompany(LocalMarketInventory $inventory, int $companyId, int $numberOfNeededUnits): array
    {
        if ($this->hasCompanyPurchasedFromInventory($inventory, $companyId)) {
            return $this->getUnitsWithOwnershipCheck($inventory, $numberOfNeededUnits, $companyId);
        }

        return $this->getUnitsWithoutOwnershipCheck($inventory, $numberOfNeededUnits);
    }

    /**
     * Calculate the number of units needed based on the loan amount and price per unit.
     */
    private function calculateNumberOfNeededUnits(float $loan, float $pricePerUnit): int
    {
        return (int) floor($loan / $pricePerUnit);
    }

    /**
     * Check if the company has previously purchased from the inventory.
     */
    private function hasCompanyPurchasedFromInventory(LocalMarketInventory $inventory, int $companyId): bool
    {
        return $inventory->hasCompanyBoughtFromInventory($companyId);
    }

    /**
     * Retrieves units from the local market inventory with an ownership check.
     */
    private function getUnitsWithOwnershipCheck(LocalMarketInventory $inventory, int $numberOfNeededUnits, int $companyId): array
    {
        // $rotationThreshold = app(LocalMurabahaSettings::class)->default_trade_order_rotation_count ?? 0;
        $rotationThreshold = 0;

        return LocalMarketInventoryUnits::join('local_market_unit_rotations', 'local_market_unit_rotations.inventory_unit_id', '=', 'local_market_inventory_units.id')
            ->where('local_market_inventory_units.status', InventoryUnitsStatus::Free)
            ->where('local_market_inventory_units.local_market_inventory_id', $inventory->id)
            ->where('local_market_unit_rotations.company_id', $companyId)
            ->where('local_market_unit_rotations.number_of_rotations', '>=', $rotationThreshold)
            ->limit($numberOfNeededUnits)
            ->get()
            ->toArray();
    }

    /**
     * Retrieve units from the local market inventory without checking ownership.
     */
    private function getUnitsWithoutOwnershipCheck(LocalMarketInventory $inventory, int $numberOfNeededUnits): array
    {
        return LocalMarketInventoryUnits::where('status', InventoryUnitsStatus::Free)
            ->where('local_market_inventory_id', $inventory->id)
            ->limit($numberOfNeededUnits)
            ->get()
            ->toArray();
    }

    /**
     * Recalculates the available inventory quantities for the given inventory IDs.
     */
    public function recalculateInventoryStock(array $inventoryIds): void
    {
        LocalMarketInventory::whereIn('id', $inventoryIds)->each(function ($inventory) {
            $inventory->refreshStockQuantities();
        });
    }

    /**
     * Updates ownership information for a batch of units.
     */
    public function updateUnitOwnership(array $units, $currentOwner, $currentOwnerType, $previousOwner, $previousOwnerType): void
    {
        $ownershipData = array_map(function ($unit) use ($currentOwner, $currentOwnerType, $previousOwner, $previousOwnerType) {
            return [
                'unit_id' => $unit,
                'current_owner' => $currentOwner,
                'current_owner_type' => $currentOwnerType,
                'previous_owner' => $previousOwner,
                'previous_owner_type' => $previousOwnerType,
            ];
        }, $units);

        $this->bulkInsertOwnershipData($ownershipData);
    }

    /**
     * Handles the bulk insertion of ownership data.
     */
    private function bulkInsertOwnershipData(array $ownershipData): void
    {
        $chunks = array_chunk($ownershipData, 3000);
        foreach ($chunks as $chunk) {
            LocalMarketUnitOwnership::insert($chunk);
        }
    }

    /**
     * Creates an order with the given details.
     */
    public function createOrder($traderOrder, $financialOrder, array $preferredTypes, int $companyId): LocalMarketOrder
    {
        return LocalMarketOrder::create([
            'source' => Trader::Lynk,
            'trader_order_id' => $traderOrder->id,
            'amount' => $financialOrder->amount->convertAndFormatByDecimal(),
            'national_id' => $financialOrder->national_id,
            'customer_name' => $financialOrder->customer_name,
            'preferred_commodity_type' => json_encode($preferredTypes),
            'company_id' => $companyId,
            'comment' => null,
            'status' => LocalMarketOrderStatus::InProgress,
        ]);
    }

    /**
     * Finds a commodity item by its ID.
     */
    public function findCommodityItem(int $commodityItemId)
    {
        return DB::table('commodity_items')->find($commodityItemId);
    }

    /**
     * Creates an inventory record for an order.
     */
    public function createOrderInventory(int $orderId, $inventory, $item)
    {
        return LocalMarketOrderHasInventory::create([
            'local_market_order_id' => $orderId,
            'local_market_inventory_id' => $inventory->id,
            'quantity' => count($inventory->units),
            'price' => $inventory->max_price,
            'measurement_id' => $item->measurement_id,
            'currency_id' => $item->currency_id,
            'location_id' => $inventory->supplier_location_id,
            'supplier_id' => $inventory->company_id,
            'previous_owner' => '',
        ]);
    }
}
