<?php

namespace App\Services\LocalMarket;

use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\CommodityItem;
use App\Models\CommodityType;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketLive;
use App\Models\Supplier;
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
            $this->logInfo('Starting live market build from scratch');

            $this->truncateMarket();
            $companies = $this->getActiveLenderCompanies();
            $inventories = $this->getActiveInventories();

            $result = $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);

            $this->logInfo('Completed live market build', $result);

            return $result;
        } catch (\Exception $e) {
            $this->logError('Failed to build live market from scratch', $e);
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
    | Commodity Item Management
    |--------------------------------------------------------------------------
    */

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
            $this->logInfo('Updating commodity item price', [
                'commodity_item_id' => $commodityItem->id,
                'new_price' => $newPrice,
            ]);

            LocalMarketLive::where('commodity_item_id', $commodityItem->id)
                ->update(['price' => $newPrice]);
        } catch (\Exception $e) {
            $this->logError('Failed to update commodity item price', $e, [
                'commodity_item_id' => $commodityItem->id,
                'new_price' => $newPrice,
            ]);
            throw $e;
        }
    }

    public function handleCommodityItemTypeUpdate(CommodityItem $commodityItem, int $commodityTypeId): void
    {
        try {
            // Update live market records for all affected inventories
            LocalMarketLive::where('commodity_item_id', $commodityItem->id)
                ->update(['commodity_type_id' => $commodityTypeId]);
        } catch (\Exception $e) {
            $this->logError('Failed to update commodity item type', $e, [
                'commodity_item_id' => $commodityItem->id,
                'commodity_type_id' => $commodityTypeId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleCommodityItemDeletion(CommodityItem $commodityItem): void
    {
        try {
            // Delete all live market records for this commodity item
            LocalMarketLive::where('commodity_item_id', $commodityItem->id)
                ->delete();
        } catch (\Exception $e) {
            $this->logError('Failed to delete commodity item records', $e, [
                'commodity_item_id' => $commodityItem->id,
            ]);
            throw $e;
        }
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
            $this->logInfo('Processing new inventory', [
                'inventory_id' => $inventory->id,
                'commodity_item_id' => $inventory->commodity_item_id,
            ]);

            if (! $this->isInventoryEligible($inventory)) {
                $this->logInfo('Inventory not eligible for live market', [
                    'inventory_id' => $inventory->id,
                    'status' => $inventory->status->value,
                ]);

                return;
            }

            $companies = $this->getActiveLenderCompanies();
            $this->createInventoryRecords($inventory, $companies);
        } catch (\Exception $e) {
            $this->logError('Failed to handle new inventory', $e, [
                'inventory_id' => $inventory->id,
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
            $this->logError('Failed to update inventory', $e, [
                'inventory_id' => $inventory->id,
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
            $this->logInfo('Starting inventory deletion process', [
                'inventory_id' => $inventory->id,
                'commodity_item_id' => $inventory->commodity_item_id,
            ]);

            $recordsDeleted = LocalMarketLive::where('inventory_id', $inventory->id)
                ->delete();

            $this->logInfo('Successfully deleted inventory records', [
                'inventory_id' => $inventory->id,
                'records_deleted' => $recordsDeleted,
            ]);
        } catch (\Exception $e) {
            $this->logError('Failed to delete inventory', $e, [
                'inventory_id' => $inventory->id,
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Supplier Management
    |--------------------------------------------------------------------------
    */

    /**
     * Handle changes in supplier status and update live market accordingly
     *
     * @param  Company  $supplier  The supplier company with changed status
     *
     * @throws \Exception If status change handling fails
     */
    public function handleSupplierStatusChange(Supplier $supplier): void
    {
        try {
            $supplierDetails = $supplier->detail;
            $inventories = $this->getActiveInventoriesForSupplier($supplier);

            if ($supplierDetails->status->is(CommoitySupplierStatus::Active)) {
                $companies = $this->getActiveLenderCompanies();
                $inventories->each(
                    fn ($inventory) => $this->createInventoryRecords($inventory, $companies)
                );
            } else {
                $this->removeInventoriesRecords($inventories);
            }

            return;
        } catch (\Exception $e) {
            $this->logError('Failed to handle supplier status change', $e, [
                'supplier_id' => $supplier->id,
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
            $this->createCompanyRecords($company, $inventories);
        } catch (\Exception $e) {
            $this->logError('Failed to handle new company', $e, [
                'company_id' => $company->id,
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
            $this->logError('Failed to remove company records', $e, [
                'company_id' => $company->id,
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Commodity Type Management
    |--------------------------------------------------------------------------
    */

    public function handleCommodityTypeDeletion(CommodityType $commodityType): void
    {
        try {
            // Delete all live market records for this commodity item
            LocalMarketLive::where('commodity_type_id', $commodityType->id)
                ->delete();
        } catch (\Exception $e) {
            $this->logError('Failed to delete commodity item records', $e, [
                'commodity_type_id' => $commodityType->id,
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

    /*
    |--------------------------------------------------------------------------
    | Helper Methods - Record Management
    |--------------------------------------------------------------------------
    */

    /**
     * Create company records in live market
     *
     * @param  Collection<LocalMarketInventory>  $inventories
     */
    private function createCompanyRecords(Company $company, Collection $inventories): void
    {
        $inventories->each(
            fn ($inventory) => $this->createLiveMarketRecord($inventory, $company, $this->calculateEligibleQuantity($inventory, $company))
        );
    }

    /**
     * Create inventory records in live market
     *
     * @param  Collection<Company>  $companies
     */
    private function createInventoryRecords(LocalMarketInventory $inventory, Collection $companies): void
    {
        $companies->each(
            fn ($company) => $this->createLiveMarketRecord($inventory, $company, $this->calculateEligibleQuantity($inventory, $company))
        );
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
        try {
            LocalMarketLive::firstOrCreate(
                [
                    'inventory_id' => $inventory->id,
                    'company_id' => $company->id,
                ],
                [
                    'commodity_item_id' => $inventory->commodity_item_id,
                    'commodity_type_id' => $inventory->commodity_type_id,
                    'price' => $inventory->item->max_price,
                    'eligible_quantity' => $eligibleQuantity,
                    'status' => $inventory->status,
                ]
            );
        } catch (\Throwable $e) {
            Log::channel('live_market')->error('Failed to create live market record', [
                'inventory_id' => $inventory->id,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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
     * Log a message to the live market channel
     */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('live_market')->$level($message, $context);
    }

    /**
     * Log an error message with exception details
     */
    private function logError(string $message, \Exception $e, array $additionalContext = []): void
    {
        $context = array_merge([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $additionalContext);

        $this->log('error', $message, $context);
    }

    /**
     * Log an info message
     */
    private function logInfo(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
}
