<?php

declare(strict_types=1);

use App\Enums\LocalMarket\InventoryUnitsStatus;
use Illuminate\Database\Migrations\Migration;
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
