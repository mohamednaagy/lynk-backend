<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $indexes = [
            'inventory_units_inventory_id_index',
            'local_market_inventory_units_status_index',
            'idx_previous_company_id_owners',
        ];
        Schema::table('local_market_inventory_units', function (Blueprint $table) use ($indexes) {
            foreach ($indexes as $index) {
                $existingIndexes = DB::table('INFORMATION_SCHEMA.STATISTICS')
                    ->select('INDEX_NAME')
                    ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
                    ->where('TABLE_NAME', 'local_market_inventory_units')
                    ->distinct()
                    ->where('INDEX_NAME', $index)
                    ->exists();
                if ($existingIndexes) {
                    $table->dropIndex($index);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('CREATE INDEX local_market_inventory_units_status_index ON local_market_inventory_units ((CAST(status AS CHAR(512))))');
        DB::statement('CREATE INDEX inventory_units_inventory_id_index ON local_market_inventory_units ((CAST(local_market_inventory_id AS CHAR(512))))');
        DB::statement('CREATE INDEX idx_previous_company_id_owners ON local_market_inventory_units ((CAST(previous_company_id_owners AS CHAR(512))))');
    }
};
