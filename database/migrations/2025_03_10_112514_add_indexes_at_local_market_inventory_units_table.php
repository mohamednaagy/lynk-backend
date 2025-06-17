<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Check if the old index exists before renaming
        $oldIndexExists = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', 'local_market_inventory_units')
            ->where('INDEX_NAME', 'idx_hold_for_optimized')
            ->exists();

        if ($oldIndexExists) {
            DB::statement('
                ALTER TABLE local_market_inventory_units
                RENAME INDEX idx_hold_for_optimized TO inventory_units_hold_for_index
            ');
        }

        // Add the new index
        DB::statement('
            ALTER TABLE local_market_inventory_units
            ADD INDEX inventory_units_eligibility_index (
                local_market_inventory_id,
                status,
                hold_for,
                deleted_at,
                previous_company_id_owner_0,
                previous_company_id_owner_1,
                previous_company_id_owner_2,
                previous_company_id_owner_3
            )
        ');
    }

    public function down()
    {
        // Drop the new index if it exists
        $newIndexExists = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', 'local_market_inventory_units')
            ->where('INDEX_NAME', 'inventory_units_eligibility_index')
            ->exists();

        if ($newIndexExists) {
            DB::statement('
                ALTER TABLE local_market_inventory_units
                DROP INDEX inventory_units_eligibility_index
            ');
        }

        // Rename the index back if it exists
        $renamedIndexExists = DB::table('INFORMATION_SCHEMA.STATISTICS')
            ->select('INDEX_NAME')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', 'local_market_inventory_units')
            ->where('INDEX_NAME', 'inventory_units_hold_for_index')
            ->exists();

        if ($renamedIndexExists) {
            DB::statement('
                ALTER TABLE local_market_inventory_units
                RENAME INDEX inventory_units_hold_for_index TO idx_hold_for_optimized
            ');
        }
    }
};
