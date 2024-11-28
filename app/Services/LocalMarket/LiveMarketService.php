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
     */
    public function buildFromScratch(?callable $progressCallback = null): array
    {
        try {
            DB::beginTransaction();

            $this->truncateMarket();
            $companies = $this->getActiveLenderCompanies();
            $inventories = $this->getActiveInventories();
            $result = $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);

            DB::commit();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to build live market from scratch', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle inventory lifecycle events
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
            Log::error('Failed to handle new inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

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
            Log::error('Failed to update inventory', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

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
                    'updated_at' => now(),
                ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update inventory price', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleInventoryDeletion(LocalMarketInventory $inventory): void
    {
        try {
            DB::beginTransaction();
            $this->removeInventoryRecords($inventory);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete inventory records', [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle company lifecycle events
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
            Log::error('Failed to handle new company', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleCompanyRemoval(Company $company): void
    {
        try {
            DB::beginTransaction();
            LocalMarketLive::where('company_id', $company->id)->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove company records', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle supplier status changes
     */
    public function handleSupplierStatusChange(Company $supplier): void
    {
        try {
            DB::beginTransaction();

            $inventories = LocalMarketInventory::where('supplier_id', $supplier->id)
                ->where('status', InventoryStatus::Active)
                ->where('available_quantity', '>', 0)
                ->get();

            if ($supplier->status !== CompanyStatus::Approved) {
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
            Log::error('Failed to handle supplier status change', [
                'supplier_id' => $supplier->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle commodity type status changes
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
            Log::error('Failed to handle commodity type status change', [
                'commodity_type_id' => $commodityType->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Helper methods
     */
    private function isInventoryEligible(LocalMarketInventory $inventory): bool
    {
        return $inventory->status === InventoryStatus::Active
            && $inventory->available_quantity > 0;
    }

    private function isCompanyEligible(Company $company): bool
    {
        return $company->status === CompanyStatus::Approved
            && $company->type === CompanyType::Lender;
    }

    private function getActiveLenderCompanies(): Collection
    {
        return Company::where('status', CompanyStatus::Approved)
            ->where('type', CompanyType::Lender)
            ->get();
    }

    private function getActiveInventories(): Collection
    {
        return LocalMarketInventory::where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    private function removeInventoryRecords(LocalMarketInventory $inventory): void
    {
        LocalMarketLive::where('inventory_id', $inventory->id)->delete();
    }

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

    private function calculateEligibleQuantity(LocalMarketInventory $inventory, Company $company): int
    {
        $unitService = new UnitService;

        return $unitService->countEligibleUnits($company, $inventory);
    }

    private function truncateMarket(): void
    {
        DB::table('local_market_live')->truncate();
    }

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

    private function reportProgress(callable $callback, LocalMarketInventory $inventory, Company $company, int $current, int $total): void
    {
        $callback([
            'inventory' => $inventory,
            'company' => $company,
            'current' => $current,
            'total' => $total,
        ]);
    }
}
