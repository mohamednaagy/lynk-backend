<?php

declare(strict_types=1);

use App\Enums\LocalMarket\InventoryUnitsStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('local_market_inventory_units')
            ->where('status', InventoryUnitsStatus::Reserved)
            ->whereNull('deleted_at')
            ->where('hold_for', 0)
            ->orderBy('id')
            ->chunkById(1000, function ($units): void {
                $ids = $units->pluck('id')->toArray();
                DB::table('local_market_inventory_units')
                    ->whereIn('id', $ids)
                    ->update(['status' => InventoryUnitsStatus::Free]);
            });

        $mismatchedInventories = $this->getMismatchedInventories();

        $mismatchedInventories
            ->chunk(1000)
            ->each(function ($chunk): void {
                $ids = $chunk->pluck('inventory_id')->all();
                DB::table('local_market_inventories')
                    ->whereIn('id', $ids)
                    ->where('is_editable', 0)
                    ->update(['is_editable' => 1]);
            });
    }

    /**
     * Get inventories whose aggregate quantities in `local_market_inventories`
     * do not match the derived aggregates from `local_market_inventory_units`.
     */
    private function getMismatchedInventories(): Collection
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
