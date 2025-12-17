<?php

namespace App\Services\LocalMarket;

use App\Enums\CompanyType;
use App\Enums\LocalMarket\InventoryStatus;
use App\Models\Company;
use App\Models\Lender;
use App\Models\LocalMarketEligibleQuantity;
use App\Models\LocalMarketInventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class EligibleQuantityService
{
    private UnitService $unitService;

    public function __construct(UnitService $unitService)
    {
        $this->unitService = $unitService;
    }

    /**
     * Build eligible quantities from scratch
     *
     * @param  callable|null  $progressCallback  Optional callback for progress reporting
     * @return array Statistics about the build process
     */
    public function buildFromScratch(?callable $progressCallback = null): array
    {
        try {
            $this->logInfo('Starting eligible quantities build from scratch');

            $this->truncateEligibleQuantities();
            $companies = $this->getLenderCompanies();
            $inventories = $this->getInventories();

            $result = $this->processInventoriesAndCompanies($companies, $inventories, $progressCallback);

            $this->logInfo('Completed eligible quantities build', $result);

            return $result;
        } catch (\Throwable $e) {
            $this->logError('Failed to build eligible quantities from scratch', $e);
            throw $e;
        }
    }

    /**
     * Rebuild eligible quantities for a specific inventory
     */
    public function rebuildForInventory(LocalMarketInventory $inventory): void
    {
        try {
            $this->logInfo('Rebuilding eligible quantities for inventory', [
                'inventory_id' => $inventory->id,
            ]);

            $inventory->status = InventoryStatus::Pending;
            $inventory->save();

            // Remove existing records
            LocalMarketEligibleQuantity::where('inventory_id', $inventory->id)->delete();

            $lenders = $this->getLenderCompanies();

            foreach ($lenders as $lender) {
                $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $lender);
                $this->createEligibleQuantityRecord($inventory, $lender, $eligibleQuantity);
            }

            $inventory->status = InventoryStatus::Active;
            $inventory->save();

            $this->logInfo('Completed rebuilding eligible quantities for inventory', [
                'inventory_id' => $inventory->id,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Failed to rebuild eligible quantities for inventory', $e, [
                'inventory_id' => $inventory->id,
            ]);
            throw $e;
        }
    }

    /**
     * Rebuild eligible quantities for a specific lender
     */
    public function rebuildForLender(Lender $lender): void
    {
        try {
            $this->logInfo('Rebuilding eligible quantities for lender', [
                'company_id' => $lender->id,
            ]);

            // Remove existing records
            LocalMarketEligibleQuantity::where('company_id', $lender->id)->delete();

            $inventories = $this->getInventories();

            foreach ($inventories as $inventory) {
                $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $lender);
                $this->createEligibleQuantityRecord($inventory, $lender, $eligibleQuantity);
            }

            $this->logInfo('Completed rebuilding eligible quantities for lender', [
                'company_id' => $lender->id,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Failed to rebuild eligible quantities for lender', $e, [
                'company_id' => $lender->id,
            ]);
            throw $e;
        }
    }

    /**
     * Delete eligible quantities for a specific inventory
     */
    public function deleteForInventory(LocalMarketInventory $inventory): void
    {
        try {
            $this->logInfo('Deleting eligible quantities for inventory', [
                'inventory_id' => $inventory->id,
            ]);

            $deletedCount = LocalMarketEligibleQuantity::where('inventory_id', $inventory->id)
                ->chunkById(100, function ($records) {
                    foreach ($records as $record) {
                        $record->delete();
                    }
                });

            $this->logInfo('Successfully deleted eligible quantities for inventory', [
                'inventory_id' => $inventory->id,
                'records_deleted' => $deletedCount,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Failed to delete eligible quantities for inventory', $e, [
                'inventory_id' => $inventory->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete eligible quantities for a specific lender
     */
    public function deleteForLender(Lender $lender): void
    {
        try {
            $this->logInfo('Deleting eligible quantities for lender', [
                'company_id' => $lender->id,
            ]);

            LocalMarketEligibleQuantity::where('company_id', $lender->id)
                ->chunkById(1000, function ($records) {
                    foreach ($records as $record) {
                        $record->delete();
                    }
                });

            $this->logInfo('Successfully deleted eligible quantities for lender', [
                'company_id' => $lender->id,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Failed to delete eligible quantities for lender', $e, [
                'company_id' => $lender->id,
            ]);
            throw $e;
        }
    }

    /**
     * Process inventories and companies to build eligible quantity records
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

        // Process in smaller chunks to avoid long locks
        $companies->chunk(50)->each(function ($companyChunk) use (
            $inventories,
            $progressCallback,
            &$current,
            &$stats
        ) {
            foreach ($companyChunk as $company) {
                foreach ($inventories as $inventory) {
                    $current++;

                    if ($progressCallback) {
                        $progressCallback([
                            'inventory' => $inventory,
                            'company' => $company,
                            'current' => $current,
                            'total' => $stats['total_operations'],
                        ]);
                    }

                    $eligibleQuantity = $this->calculateEligibleQuantity($inventory, $company);
                    $this->createEligibleQuantityRecord($inventory, $company, $eligibleQuantity);
                    $stats['records_created']++;
                }
            }
        });

        return $stats;
    }

    /**
     * Create a single eligible quantity record
     */
    private function createEligibleQuantityRecord(
        LocalMarketInventory $inventory,
        Lender $lender,
        int $eligibleQuantity
    ): void {
        try {
            LocalMarketEligibleQuantity::create([
                'inventory_id' => $inventory->id,
                'company_id' => $lender->id,
                'eligible_quantity' => $eligibleQuantity,
            ]);
        } catch (\Throwable $e) {
            $this->logError('Failed to create eligible quantity record', $e, [
                'inventory_id' => $inventory->id,
                'company_id' => $lender->id,
                'eligible_quantity' => $eligibleQuantity,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Calculate eligible quantity for inventory-company combination
     */
    private function calculateEligibleQuantity(LocalMarketInventory $inventory, Lender $lender): int
    {
        return $this->unitService->countEligibleUnits($lender, $inventory);
    }

    /**
     * Get all lender companies
     */
    private function getLenderCompanies(): Collection
    {
        return Lender::query()
            ->where('type', CompanyType::Lender)
            ->get();
    }

    /**
     * Get all active inventories
     */
    private function getInventories(): Collection
    {
        return LocalMarketInventory::query()
            ->get();
    }

    /**
     * Clear all eligible quantity records
     */
    private function truncateEligibleQuantities(): void
    {
        LocalMarketEligibleQuantity::truncate();
    }

    /**
     * Log an error message
     */
    private function logError(string $message, \Throwable $e, array $context = []): void
    {
        Log::channel('live_market')->error($message, array_merge([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], $context));
    }

    /**
     * Log an info message
     */
    private function logInfo(string $message, array $context = []): void
    {
        Log::channel('live_market')->info($message, $context);
    }
}
