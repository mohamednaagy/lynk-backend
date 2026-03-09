<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocalMarketInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefreshQuantitiesLocalMarketInventories extends Command
{
    protected $signature = 'inventories:refresh-quantities
                            {--chunk-size=100 : Number of records to process at once}';

    protected $description = 'Find local market inventories with mismatched quantity/unit aggregates and refresh their stock quantities';

    public function handle(): int
    {
        $chunkSize = (int) ($this->option('chunk-size'));
        $mismatchedInventories = $this->getMismatchedInventoryIds();

        if ($mismatchedInventories->isEmpty()) {
            $this->info('No local market inventories with mismatched quantities found.');

            return 0;
        }

        $totalRecords = $mismatchedInventories->count();
        $this->info("Found {$totalRecords} local market inventories with mismatched quantities.");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $failed = 0;
        foreach ($mismatchedInventories->chunk($chunkSize) as $chunk) {
            Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info("Processing chunk of {$chunk} inventories");
            $ids = $chunk->pluck('inventory_id')->all();
            $inventories = LocalMarketInventory::query()
                ->whereIn('id', $ids)
                ->get();

            foreach ($inventories as $inventory) {
                try {
                    DB::transaction(function () use ($inventory): void {
                        $inventory->refreshStockQuantities(forceRebuildEligibility : true);
                        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info("Refreshed inventory id = {$inventory->id}");
                    });
                    $processed++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->newLine();
                    $this->error("Failed inventory id={$inventory->id}: {$e->getMessage()}");
                }
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully refreshed {$processed} local market inventories.");

        if ($failed > 0) {
            $this->warn("Failed to refresh {$failed} inventory(ies).");
        }

        return $failed > 0 ? 1 : 0;
    }

    /**
     * Run the mismatch query: inventories whose commodity_item has inconsistent
     * aggregates between local_market_inventories and local_market_inventory_units.
     *
     * @return \Illuminate\Support\Collection<int, object{inventory_id: int}>
     */
    private function getMismatchedInventoryIds(): \Illuminate\Support\Collection
    {
        $sql = <<<'SQL'
                SELECT 
                    lmi.id AS inventory_id,
                    COALESCE(lmi.available_quantity, 0) AS available_in_inventory,
                    COALESCE(lmiu.available_units, 0) AS available_in_units,
                    COALESCE(lmi.reserved_items, 0) AS reserved_in_inventory,
                    COALESCE(lmiu.reserved_units, 0) AS reserved_in_units
                FROM local_market_inventories lmi
                LEFT JOIN (
                    SELECT 
                        local_market_inventory_id,
                        SUM(CASE 
                                WHEN hold_for = 0 AND status = 1 THEN 1 
                                ELSE 0 
                            END) AS available_units,
                        SUM(CASE 
                                WHEN hold_for != 0 AND status = 2 THEN 1 
                                ELSE 0 
                            END) AS reserved_units
                    FROM local_market_inventory_units
                    WHERE deleted_at IS NULL
                    GROUP BY local_market_inventory_id
                ) lmiu ON lmiu.local_market_inventory_id = lmi.id
                WHERE lmi.deleted_at IS NULL
                AND (
                        COALESCE(lmi.available_quantity, 0) != COALESCE(lmiu.available_units, 0)
                    OR COALESCE(lmi.reserved_items, 0) != COALESCE(lmiu.reserved_units, 0)
                )
                ORDER BY lmi.id DESC;
            SQL;

        $rows = DB::select($sql);

        return collect($rows);
    }
}
