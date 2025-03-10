<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // DB::statement('
        //         ALTER TABLE local_market_inventory_units
        //         RENAME INDEX idx_hold_for_optimized TO inventory_units_hold_for_index
        //     ');

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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('
            ALTER TABLE local_market_inventory_units 
            DROP INDEX inventory_units_eligibility_index
        ');

        // DB::statement('
        //     ALTER TABLE local_market_inventory_units
        //     RENAME INDEX inventory_units_hold_for_index TO idx_hold_for_optimized
        // ');
    }
};
