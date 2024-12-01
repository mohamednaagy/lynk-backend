<?php

namespace App\Services\LocalMarket;

use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\CommodityItem;
use App\Models\CommodityType;
use App\Models\Company;
use App\Models\CompanySupplierDetail;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketLive;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class LiveMarketService
{
    /*
    |--------------------------------------------------------------------------
    | Main Market Building Operations
    |--------------------------------------------------------------------------
    */

    /**
     * Build the live market from scratch
     *
     * @param  callable|null  $progressCallback  Optional callback for progress reporting
     * @return array Statistics about the build process
     *
     * @throws \Exception If build process fails
     */
    public function buildFromScratch(?callable $progressCallback = null): array
    {
        try {
            $this->truncateMarket();
            $companies = $this->getActiveLenderCompanies();
            $inventories = $this->getActiveInventories();
            $result = $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to build live market from scratch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Process inventories and companies to build market records
     *
     * @param  Collection  $companies  Collection of active lender companies
     * @param  Collection  $inventories  Collection of active inventories
     * @param  callable|null  $progressCallback  Optional callback for progress reporting
     * @return array Processing statistics
     */
    private function processInventoriesAndCompanies(
        Collection $companies,
        Collection $inventories,
        ?callable $progressCallback
    ): array {
        $stats = [
            'companies_processed' => $companies->count(),
            'inventories_processed' => $inventories->count(),
            'total_operations' => $companies->count() * $inventories->count(),
            'records_created' => 0,
        ];

        $current = 0;

        foreach ($inventories as $inventory) {
            foreach ($companies as $company) {
                $current++;

                if ($progressCallback) {
                    $this->reportProgress($progressCallback, $inventory, $company, $current, $stats['total_operations']);
                }

                if ($eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company)) {
                    $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
                    $stats['records_created']++;
                }
            }
        }

        return $stats;
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Management
    |--------------------------------------------------------------------------
    */

    /**
     * Handle creation of new inventory in live market
     *
     * @param  LocalMarketInventory  $inventory  The new inventory to process
     *
     * @throws \Exception If handling fails
     */
    public function handleNewInventory(LocalMarketInventory $inventory): void
    {
        try {
            if (! $this->isInventoryEligible($inventory)) {
                return;
            }

            $companies = $this->getActiveLenderCompanies();
            $this->createLiveMarketRecords($inventory, $companies);
        } catch (\Exception $e) {
            $this->logError('Failed to handle new inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle updates to existing inventory in live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory being updated
     *
     * @throws \Exception If update fails
     */
    public function handleInventoryUpdate(LocalMarketInventory $inventory): void
    {
        try {
            $this->removeInventoryRecords($inventory);

            if ($this->isInventoryEligible($inventory)) {
                $this->handleNewInventory($inventory);
            }
        } catch (\Exception $e) {
            $this->logError('Failed to update inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle price updates for commodity item and update all related inventories
     *
     * @param  int  $commodityItemId  The ID of the commodity item with updated price
     * @param  float  $newPrice  The new price for the commodity item
     *
     * @throws \Exception If price update fails
     */
    public function handleCommodityItemPriceUpdate(CommodityItem $commodityItem, float $newPrice): void
    {
        try {
            // Update live market records for all affected inventories
            LocalMarketLive::where('inventory_id', $commodityItem->id)
                ->update(['price' => $newPrice]);
        } catch (\Exception $e) {
            $this->logError('Failed to update commodity item price', [
                'commodity_item_id' => $commodityItem->id,
                'new_price' => $newPrice,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleCommodityItemTypeUpdate(CommodityItem $commodityItem, int $commodityTypeId): void
    {
        try {
            // Update live market records for all affected inventories
            LocalMarketLive::where('commodity_id', $commodityItem->id)
                ->update(['commodity_type_id' => $commodityTypeId]);
        } catch (\Exception $e) {
            $this->logError('Failed to update commodity item type', [
                'commodity_item_id' => $commodityItem->id,
                'commodity_type_id' => $commodityTypeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle deletion of inventory from live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to delete
     *
     * @throws \Exception If deletion fails
     */
    public function handleInventoryDeletion(LocalMarketInventory $inventory): void
    {
        try {
            $this->removeInventoryRecords($inventory);
        } catch (\Exception $e) {
            $this->logError('Failed to delete inventory records', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Company Management
    |--------------------------------------------------------------------------
    */

    /**
     * Handle new company addition to live market
     *
     * @param  Company  $company  The new company to process
     *
     * @throws \Exception If handling fails
     */
    public function handleNewCompany(Company $company): void
    {
        try {
            if (! $this->isCompanyEligible($company)) {
                return;
            }

            $inventories = $this->getActiveInventories();
            $this->createLiveMarketRecords($company, $inventories);
        } catch (\Exception $e) {
            $this->logError('Failed to handle new company', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove company and its associated records from live market
     *
     * @param  Company  $company  The company to remove
     *
     * @throws \Exception If removal fails
     */
    public function handleCompanyRemoval(Company $company): void
    {
        try {
            LocalMarketLive::where('company_id', $company->id)->delete();
        } catch (\Exception $e) {
            $this->logError('Failed to remove company records', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle changes in supplier status and update live market accordingly
     *
     * @param  Company  $supplier  The supplier company with changed status
     *
     * @throws \Exception If status change handling fails
     */
    public function handleSupplierStatusChange(CompanySupplierDetail $companySupplierDetail): void
    {
        try {
            $supplier = $companySupplierDetail->company;
            $inventories = $this->getActiveInventoriesForSupplier($supplier);

            if ($companySupplierDetail->status->is(CommoitySupplierStatus::Active)) {
                $companies = $this->getActiveLenderCompanies();
                $inventories->each(
                    fn ($inventory) => $this->createLiveMarketRecords($inventory, $companies)
                );
            } else {
                $this->removeInventoriesRecords($inventories);
            }

            return;
        } catch (\Exception $e) {
            $this->logError('Failed to handle supplier status change', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Commodity Type Management
    |--------------------------------------------------------------------------
    */

    /**
     * Handle changes in commodity type status and update live market
     *
     * @param  CommodityType  $commodityType  The commodity type with changed status
     *
     * @throws \Exception If status change handling fails
     */
    public function handleCommodityTypeStatusChange(CommodityType $commodityType): void
    {
        try {
            $inventories = $this->getActiveInventoriesForCommodityType($commodityType);

            if (! $commodityType->is_active) {
                $this->removeInventoriesRecords($inventories);

                return;
            }

            $companies = $this->getActiveLenderCompanies();
            $inventories->each(
                fn ($inventory) => $this->createLiveMarketRecords($inventory, $companies)
            );
        } catch (\Exception $e) {
            $this->logError('Failed to handle commodity type status change', [
                'commodity_type_id' => $commodityType->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Eligibility Checks
    |--------------------------------------------------------------------------
    */

    /**
     * Check if inventory meets eligibility criteria for live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to check
     * @return bool True if eligible, false otherwise
     */
    private function isInventoryEligible(LocalMarketInventory $inventory): bool
    {
        return $inventory->status->is(InventoryStatus::Active)
            && $inventory->available_quantity > 0;
    }

    /**
     * Check if company meets eligibility criteria for live market
     *
     * @param  Company  $company  The company to check
     * @return bool True if eligible, false otherwise
     */
    private function isCompanyEligible(Company $company): bool
    {
        return $company->status->is(CompanyStatus::Approved)
            && $company->type->is(CompanyType::Lender);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Data Retrieval
    |--------------------------------------------------------------------------
    */

    /**
     * Retrieve all active lender companies
     *
     * @return Collection Collection of active lender companies
     */
    private function getActiveLenderCompanies(): Collection
    {
        return Company::query()
            ->where('status', CompanyStatus::Approved)
            ->where('type', CompanyType::Lender)
            ->get();
    }

    /**
     * Retrieve all active inventories
     *
     * @return Collection Collection of active inventories
     */
    private function getActiveInventories(): Collection
    {
        return LocalMarketInventory::query()
            ->where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    /**
     * Get active inventories for a specific supplier
     *
     * @param  Company  $supplier  The supplier company
     * @return Collection Collection of active inventories for the supplier
     */
    private function getActiveInventoriesForSupplier(Company $supplier): Collection
    {
        return LocalMarketInventory::query()
            ->where('company_id', $supplier->id)
            ->where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    /**
     * Get active inventories for a specific commodity type
     *
     * @param  CommodityType  $commodityType  The commodity type
     * @return Collection Collection of active inventories for the commodity type
     */
    private function getActiveInventoriesForCommodityType(CommodityType $commodityType): Collection
    {
        return LocalMarketInventory::query()
            ->where('commodity_type_id', $commodityType->id)
            ->where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Record Management
    |--------------------------------------------------------------------------
    */

    /**
     * Create live market records for inventory-company combinations
     *
     * @param  LocalMarketInventory|Company  $primary  Primary entity (inventory or company)
     * @param  Collection  $records  Collection of secondary entities to create records with
     */
    private function createLiveMarketRecords(LocalMarketInventory|Company $primary, Collection $records): void
    {
        $isPrimaryInventory = $primary instanceof LocalMarketInventory;

        $records->each(function ($record) use ($primary, $isPrimaryInventory) {
            $inventory = $isPrimaryInventory ? $primary : $record;
            $company = $isPrimaryInventory ? $record : $primary;

            if ($eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company)) {
                $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
            }
        });
    }

    /**
     * Create a single live market record
     *
     * @param  LocalMarketInventory  $inventory  The inventory for the record
     * @param  Company  $company  The company for the record
     * @param  int  $eligibleQuantity  The calculated eligible quantity
     */
    private function createLiveMarketRecord(LocalMarketInventory $inventory, Company $company, int $eligibleQuantity): void
    {
        LocalMarketLive::create([
            'inventory_id' => $inventory->id,
            'commodity_item_id' => $inventory->commodity_item_id,
            'commodity_type_id' => $inventory->commodity_type_id,
            'company_id' => $company->id,
            'price' => $inventory->item->max_price,
            'eligible_quantity' => $eligibleQuantity,
            'status' => $inventory->status,
        ]);
    }

    /**
     * Remove all live market records for a specific inventory
     *
     * @param  LocalMarketInventory  $inventory  The inventory to remove records for
     */
    private function removeInventoryRecords(LocalMarketInventory $inventory): void
    {
        LocalMarketLive::where('inventory_id', $inventory->id)->delete();
    }

    /**
     * Remove live market records for multiple inventories
     *
     * @param  Collection  $inventories  Collection of inventories to remove records for
     */
    private function removeInventoriesRecords(Collection $inventories): void
    {
        $inventories->each(fn ($inventory) => $this->removeInventoryRecords($inventory));
    }

    /**
     * Clear all records from the live market
     */
    private function truncateMarket(): void
    {
        LocalMarketLive::truncate();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Progress & Logging
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate eligible quantity for inventory-company combination
     *
     * @param  LocalMarketInventory  $inventory  The inventory to check
     * @param  Company  $company  The company to check against
     * @return int The calculated eligible quantity
     */
    private function calculateEligibleQuantity(LocalMarketInventory $inventory, Company $company): int
    {
        $unitService = new UnitService;

        return $unitService->countEligibleUnits($company, $inventory);
    }

    /**
     * Report progress during market building
     *
     * @param  callable  $callback  The progress callback function
     * @param  LocalMarketInventory  $inventory  Current inventory being processed
     * @param  Company  $company  Current company being processed
     * @param  int  $current  Current operation number
     * @param  int  $total  Total number of operations
     */
    private function reportProgress(callable $callback, LocalMarketInventory $inventory, Company $company, int $current, int $total): void
    {
        $callback([
            'inventory' => $inventory,
            'company' => $company,
            'current' => $current,
            'total' => $total,
        ]);
    }

    /**
     * Log error messages to the live market channel
     *
     * @param  string  $message  Error message
     * @param  array  $context  Additional context for the error
     */
    private function logError(string $message, array $context = []): void
    {
        Log::channel('live_market')->error($message, $context);
    }

    /**
     * Log informational messages to the live market channel
     *
     * @param  string  $message  Info message
     * @param  array  $context  Additional context for the message
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('live_market')->info($message, $context);
    }
}
