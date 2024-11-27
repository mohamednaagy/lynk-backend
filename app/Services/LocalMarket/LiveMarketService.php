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
        // First truncate the table
        $this->truncateMarket();

        // Get all active companies and inventories
        $companies = Company::where('status', CompanyStatus::Approved)
            ->where('type', CompanyType::Lender)
            ->get();

        $inventories = LocalMarketInventory::where('status', InventoryStatus::Active)
            ->where('available_quantity', '>', 0)
            ->get();

        // Calculate total for progress tracking
        $total = $companies->count() * $inventories->count();
        $current = 0;
        $recordsCreated = 0;

        // For each inventory, create records for all companies
        foreach ($inventories as $inventory) {
            foreach ($companies as $company) {
                $current++;

                // If callback provided, call it with progress info
                if ($progressCallback) {
                    $progressCallback([
                        'inventory' => $inventory,
                        'company' => $company,
                        'current' => $current,
                        'total' => $total,
                    ]);
                }

                $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

                if ($eligibleQuantity > 0) {
                    LocalMarketLive::create([
                        'inventory_id' => $inventory->id,
                        'commodity_item_id' => $inventory->commodity_item_id,
                        'commodity_type_id' => $inventory->commodity_type_id,
                        'supplier_location_id' => $inventory->supplier_location_id,
                        'supplier_id' => $inventory->supplier->id,
                        'company_id' => $company->id,
                        'price' => $inventory->max_price,
                        'eligible_quantity' => $eligibleQuantity,
                        'status' => $inventory->status,
                    ]);
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
     * Handle inventory deletion
     */
    public function handleInventoryDeletion(LocalMarketInventory $inventory): void
    {
        LocalMarketLive::where('inventory_id', $inventory->id)->delete();
    }

    /**
     * Handle new inventory creation
     */
    public function handleNewInventory(LocalMarketInventory $inventory): void
    {
        // Only process active inventories with available quantity
        if ($inventory->status !== InventoryStatus::Active || $inventory->available_quantity <= 0) {
            return;
        }

        // Get all active companies
        $companies = Company::where('status', 'active')->get();

        // Create records for each company
        foreach ($companies as $company) {
            $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);

            if ($eligibleQuantity > 0) {
                LocalMarketLive::create([
                    'inventory_id' => $inventory->id,
                    'commodity_item_id' => $inventory->commodity_item_id,
                    'commodity_type_id' => $inventory->commodity_type_id,
                    'supplier_location_id' => $inventory->supplier_location_id,
                    'supplier_id' => $inventory->supplier->id,
                    'company_id' => $company->id,
                    'price' => $inventory->max_price,
                    'eligible_quantity' => $eligibleQuantity,
                    'status' => $inventory->status,
                ]);
            }
        }
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
