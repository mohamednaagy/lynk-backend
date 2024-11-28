<?php

namespace App\Services\LocalMarket;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\Company;
use App\Models\LocalMarketInventory;
use App\Models\LocalMarketLive;
use Illuminate\Support\Facades\DB;

class LiveMarketService
{
    /**
     * Build the live market from scratch
     */
    public function buildFromScratch(?callable $progressCallback = null): array
    {
        $this->truncateMarket();

        $companies = $this->getActiveLenderCompanies();
        $inventories = $this->getActiveInventories();

        return $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);
    }

    /**
     * Process new inventory creation
     */
    public function handleNewInventory(LocalMarketInventory $inventory): void
    {
        if (! $this->isInventoryEligible($inventory)) {
            return;
        }

        $companies = $this->getActiveLenderCompanies();
        $this->createLiveMarketRecordsForInventory($inventory, $companies);
    }

    /**
     * Process inventory updates
     */
    public function handleInventoryUpdate(LocalMarketInventory $inventory): void
    {
        $this->removeInventoryRecords($inventory);

        if ($this->isInventoryEligible($inventory)) {
            $this->handleNewInventory($inventory);
        }
    }

    /**
     * Handle price updates efficiently
     */
    public function handleInventoryPriceUpdate(LocalMarketInventory $inventory): void
    {
        if (! $this->isInventoryEligible($inventory)) {
            return;
        }

        LocalMarketLive::where('inventory_id', $inventory->id)
            ->update([
                'price' => $inventory->max_price,
            ]);
    }

    /**
     * Remove inventory from live market
     */
    public function handleInventoryDeletion(LocalMarketInventory $inventory): void
    {
        $this->removeInventoryRecords($inventory);
    }

    /**
     * Process new company addition
     */
    public function handleNewCompany(Company $company): void
    {
        if (! $this->isCompanyEligible($company)) {
            return;
        }

        $inventories = $this->getActiveInventories();
        $this->createLiveMarketRecordsForCompany($company, $inventories);
    }

    /**
     * Remove company from live market
     */
    public function handleCompanyRemoval(Company $company): void
    {
        LocalMarketLive::where('company_id', $company->id)->delete();
    }

    /**
     * Check if inventory is eligible for live market
     */
    private function isInventoryEligible(LocalMarketInventory $inventory): bool
    {
        return $inventory->status === InventoryStatus::Active
            && $inventory->available_quantity > 0;
    }

    /**
     * Check if company is eligible for live market
     */
    private function isCompanyEligible(Company $company): bool
    {
        return $company->status === CompanyStatus::Approved
            && $company->type === CompanyType::Lender;
    }

    /**
     * Get all active lender companies
     */
    private function getActiveLenderCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        return Company::where('status', CompanyStatus::Approved)
            ->where('type', CompanyType::Lender)
            ->get();
    }

    /**
     * Get all active inventories
     */
    private function getActiveInventories(): \Illuminate\Database\Eloquent\Collection
    {
        return LocalMarketInventory::where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();
    }

    /**
     * Remove inventory records from live market
     */
    private function removeInventoryRecords(LocalMarketInventory $inventory): void
    {
        LocalMarketLive::where('inventory_id', $inventory->id)->delete();
    }

    /**
     * Create live market records for inventory
     */
    private function createLiveMarketRecordsForInventory(LocalMarketInventory $inventory, \Illuminate\Database\Eloquent\Collection $companies): void
    {
        foreach ($companies as $company) {
            $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

            if ($eligibleQuantity > 0) {
                $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
            }
        }
    }

    /**
     * Create live market records for company
     */
    private function createLiveMarketRecordsForCompany(Company $company, \Illuminate\Database\Eloquent\Collection $inventories): void
    {
        foreach ($inventories as $inventory) {
            $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

            if ($eligibleQuantity > 0) {
                $this->createLiveMarketRecord($inventory, $company, $eligibleQuantity);
            }
        }
    }

    /**
     * Create single live market record
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
     * Process inventories and companies for market build
     */
    private function processInventoriesAndCompanies(
        \Illuminate\Database\Eloquent\Collection $companies,
        \Illuminate\Database\Eloquent\Collection $inventories,
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
     * Calculate eligible quantity for a company
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
}
