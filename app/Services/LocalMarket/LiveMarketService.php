<?php

namespace App\Services\LocalMarket;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\CommodityType;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketLive;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiveMarketService
{
    /**
     * Build the live market from scratch
     *
     * @param  callable|null  $progressCallback  Callback function to report progress
     * @return array{companies_processed: int, inventories_processed: int, total_operations: int, records_created: int}
     *
     * @throws \Exception When build process fails
     */
    public function buildFromScratch(?callable $progressCallback = null): array
    {
        try {
            // DB::beginTransaction();

            $this->truncateMarket();
            $companies = $this->getActiveLenderCompanies();
            $inventories = $this->getActiveInventories();
            $result = $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);

            // DB::commit();

            return $result;
        } catch (\Exception $e) {
            // DB::rollBack();
            $this->logError('Failed to build live market from scratch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle new inventory addition to live market
     *
     * @param  LocalMarketInventory  $inventory  The new inventory to process
     *
     * @throws \Exception When inventory processing fails
     */
    public function handleNewInventory(LocalMarketInventory $inventory): void
    {
        try {
            if (! $this->isInventoryEligible($inventory)) {
                return;
            }

            DB::beginTransaction();

            $companies = $this->getActiveLenderCompanies();
            $this->createLiveMarketRecords($inventory, $companies);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to handle new inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update existing inventory in live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to update
     *
     * @throws \Exception When update process fails
     */
    public function handleInventoryUpdate(LocalMarketInventory $inventory): void
    {
        try {
            DB::beginTransaction();

            $this->removeInventoryRecords($inventory);

            if ($this->isInventoryEligible($inventory)) {
                $this->handleNewInventory($inventory);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to update inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update inventory price in live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory with price change
     *
     * @throws \Exception When price update fails
     */
    public function handleInventoryPriceUpdate(LocalMarketInventory $inventory): void
    {
        try {
            if (! $this->isInventoryEligible($inventory)) {
                return;
            }

            DB::beginTransaction();

            LocalMarketLive::where('inventory_id', $inventory->id)
                ->update([
                    'price' => $inventory->max_price,
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to update inventory price', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove inventory from live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to remove
     *
     * @throws \Exception When deletion fails
     */
    public function handleInventoryDeletion(LocalMarketInventory $inventory): void
    {
        try {
            DB::beginTransaction();
            $this->removeInventoryRecords($inventory);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to delete inventory records', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Process new company addition to live market
     *
     * @param  Company  $company  The new company to process
     *
     * @throws \Exception When company processing fails
     */
    public function handleNewCompany(Company $company): void
    {
        try {
            if (! $this->isCompanyEligible($company)) {
                return;
            }

            DB::beginTransaction();

            $inventories = $this->getActiveInventories();
            $this->createLiveMarketRecords($company, $inventories);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to handle new company', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Remove company from live market
     *
     * @param  Company  $company  The company to remove
     *
     * @throws \Exception When company removal fails
     */
    public function handleCompanyRemoval(Company $company): void
    {
        try {
            DB::beginTransaction();
            LocalMarketLive::where('company_id', $company->id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to remove company records', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle supplier status changes in live market
     *
     * @param  Company  $supplier  The supplier with status change
     *
     * @throws \Exception When status change processing fails
     */
    public function handleSupplierStatusChange(Company $supplier): void
    {
        try {
            DB::beginTransaction();

            $inventories = LocalMarketInventory::where('supplier_id', $supplier->id)
                ->where('status', InventoryStatus::Active)
                ->where('available_quantity', '>', 0)
                ->get();

            if (! $supplier->status->is(CompanyStatus::Approved)) {
                foreach ($inventories as $inventory) {
                    $this->removeInventoryRecords($inventory);
                }
            } else {
                $companies = $this->getActiveLenderCompanies();
                foreach ($inventories as $inventory) {
                    $this->createLiveMarketRecords($inventory, $companies);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to handle supplier status change', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle commodity type status changes in live market
     *
     * @param  CommodityType  $commodityType  The commodity type with status change
     *
     * @throws \Exception When status change processing fails
     */
    public function handleCommodityTypeStatusChange(CommodityType $commodityType): void
    {
        try {
            DB::beginTransaction();

            $inventories = LocalMarketInventory::where('commodity_type_id', $commodityType->id)
                ->where('status', InventoryStatus::Active)
                ->where('available_quantity', '>', 0)
                ->get();

            if (! $commodityType->is_active) {
                foreach ($inventories as $inventory) {
                    $this->removeInventoryRecords($inventory);
                }
            } else {
                $companies = $this->getActiveLenderCompanies();
                foreach ($inventories as $inventory) {
                    $this->createLiveMarketRecords($inventory, $companies);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Failed to handle commodity type status change', [
                'commodity_type_id' => $commodityType->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if inventory is eligible for live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to check
     * @return bool True if inventory is eligible
     */
    private function isInventoryEligible(LocalMarketInventory $inventory): bool
    {
        return $inventory->status->is(InventoryStatus::Active)
            && $inventory->available_quantity > 0;
    }

    /**
     * Check if company is eligible for live market
     *
     * @param  Company  $company  The company to check
     * @return bool True if company is eligible
     */
    private function isCompanyEligible(Company $company): bool
    {
        return $company->status->is(CompanyStatus::Approved)
            && $company->type->is(CompanyType::Lender);
    }

    /**
     * Get all active lender companies
     *
     * @return Collection<Company> Collection of active lender companies
     */
    private function getActiveLenderCompanies(): Collection
    {
        return Company::where('status', CompanyStatus::Approved)
            ->where('type', CompanyType::Lender)
            ->get();
    }

    /**
     * Get all active inventories
     *
     * @return Collection<LocalMarketInventory> Collection of active inventories
     */
    private function getActiveInventories(): Collection
    {
        return LocalMarketInventory::where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    /**
     * Remove inventory records from live market
     *
     * @param  LocalMarketInventory  $inventory  The inventory to remove records for
     */
    private function removeInventoryRecords(LocalMarketInventory $inventory): void
    {
        LocalMarketLive::where('inventory_id', $inventory->id)->delete();
    }

    /**
     * Create live market records for inventory-company combinations
     *
     * @param  LocalMarketInventory|Company  $primary  The primary entity (inventory or company)
     * @param  Collection  $records  Collection of related records to process
     */
    private function createLiveMarketRecords(mixed $primary, Collection $records): void
    {
        $isPrimaryInventory = $primary instanceof LocalMarketInventory;

        foreach ($records as $record) {
            $inventory = $isPrimaryInventory ? $primary : $record;
            $company = $isPrimaryInventory ? $record : $primary;

            $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

            if ($eligibleQuantity > 0) {
                $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
            }
        }
    }

    /**
     * Create a single live market record
     *
     * @param  LocalMarketInventory  $inventory  The inventory for the record
     * @param  Company  $company  The company for the record
     * @param  int  $eligibleQuantity  The eligible quantity for the record
     */
    private function createLiveMarketRecord(LocalMarketInventory $inventory, Company $company, int $eligibleQuantity): void
    {
        LocalMarketLive::create([
            'inventory_id' => $inventory->id,
            'commodity_type_id' => $inventory->commodity_type_id,
            'company_id' => $company->id,
            'price' => $inventory->max_price,
            'eligible_quantity' => $eligibleQuantity,
            'status' => $inventory->status,
        ]);
    }

    /**
     * Calculate eligible quantity for inventory-company combination
     *
     * @param  LocalMarketInventory  $inventory  The inventory to check
     * @param  Company  $company  The company to check against
     * @return int The eligible quantity
     */
    private function calculateEligibleQuantity(LocalMarketInventory $inventory, Company $company): int
    {
        $unitService = new UnitService;

        return $unitService->countEligibleUnits($company, $inventory);
    }

    /**
     * Truncate the live market table
     */
    private function truncateMarket(): void
    {
        DB::table('local_market_live')->truncate();
    }

    /**
     * Process inventories and companies for market build
     *
     * @param  Collection<Company>  $companies  Companies to process
     * @param  Collection<LocalMarketInventory>  $inventories  Inventories to process
     * @param  callable|null  $progressCallback  Optional callback for progress reporting
     * @return array{companies_processed: int, inventories_processed: int, total_operations: int, records_created: int}
     */
    private function processInventoriesAndCompanies(
        Collection $companies,
        Collection $inventories,
        ?callable $progressCallback
    ): array {
        $total = $companies->count() * $inventories->count();
        $current = 0;
        $recordsCreated = 0;

        foreach ($inventories as $inventory) {
            foreach ($companies as $company) {
                $current++;

                if ($progressCallback) {
                    $this->reportProgress($progressCallback, $inventory, $company, $current, $total);
                }

                $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

                if ($eligibleQuantity > 0) {
                    $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
                    $recordsCreated++;
                }
            }
        }

        return [
            'companies_processed' => $companies->count(),
            'inventories_processed' => $inventories->count(),
            'total_operations' => $total,
            'records_created' => $recordsCreated,
        ];
    }

    /**
     * Report progress during market build
     *
     * @param  callable  $callback  The callback function to report progress
     * @param  LocalMarketInventory  $inventory  Current inventory being processed
     * @param  Company  $company  Current company being processed
     * @param  int  $current  Current operation number
     * @param  int  $total  Total operations to process
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
     * Log error with live market channel
     *
     * @param  string  $message  Error message
     * @param  array  $context  Additional context for the error
     */
    private function logError(string $message, array $context = []): void
    {
        Log::channel('live_market')->error($message, $context);
    }
}
