<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LocalMarketInventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshQuantitiesLocalMarketInventories extends Command
{
    protected $signature = 'inventories:refresh-quantities
                            {--chunk-size=500 : Number of records to process at once}
                            {--only-ids : Only display mismatched inventory IDs without applying any changes}';

    protected $description = 'Find local market inventories with mismatched quantity/unit aggregates and refresh their stock quantities';

    public function handle(): int
    {
        $chunkSize = (int) ($this->option('chunk-size'));
        $onlyIds = (bool) $this->option('only-ids');
        $inventoryIds = $this->getMismatchedInventoryIds();

        if ($inventoryIds->isEmpty()) {
            $this->info('No local market inventories with mismatched quantities found.');

            return 0;
        }

        $totalRecords = $inventoryIds->count();
        $this->info("Found {$totalRecords} local market inventories with mismatched quantities.");

        if ($onlyIds) {
            $this->table(
                [
                    'Inventory ID',
                    'Available (inventory)',
                    'Available (units)',
                    'Reserved (inventory)',
                    'Reserved (units)',
                ],
                $inventoryIds
                    ->map(static function (object $row): array {
                        return [
                            'inventory_id' => $row->inventory_id,
                            'available_in_inventory' => $row->available_in_inventory,
                            'available_in_units' => $row->available_in_units,
                            'reserved_in_inventory' => $row->reserved_in_inventory,
                            'reserved_in_units' => $row->reserved_in_units,
                        ];
                    })
                    ->all()
            );

            return 0;
        }

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $failed = 0;
        foreach ($inventoryIds->chunk($chunkSize) as $chunk) {
            $ids = $chunk->pluck('inventory_id')->all();
            $inventories = LocalMarketInventory::query()
                ->whereIn('id', $ids)
                ->get();

            foreach ($inventories as $inventory) {
                try {
                    DB::transaction(function () use ($inventory): void {
                        $inventory->markAsEditable();
                        $inventory->refreshStockQuantities(true);
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
                    COALESCE(lmi.reserved_items, 0) AS reserved_in_inventory,
                    COALESCE(lmiu.available_units, 0) AS available_in_units,
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
