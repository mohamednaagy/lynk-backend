<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $inventoryChunkSize = 1000;

        DB::table('local_market_inventories')
            ->select('id')
            ->whereNotNull('deleted_at')
            ->orderBy('id')
            ->chunkById($inventoryChunkSize, function ($inventories): void {
                $inventoryIds = $inventories->pluck('id')->all();

                if (empty($inventoryIds)) {
                    return;
                }

                DB::table('local_market_inventory_units as u')
                    ->join('local_market_inventories as i', 'i.id', '=', 'u.local_market_inventory_id')
                    ->whereNull('u.deleted_at')
                    ->whereNotNull('i.deleted_at')
                    ->whereIn('u.local_market_inventory_id', $inventoryIds)
                    ->update([
                        'u.deleted_at' => DB::raw('i.deleted_at'),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally left empty because restoring soft-deleted rows is not deterministic.
    }
};
