<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Using raw SQL to update the default value
        DB::statement('ALTER TABLE local_market_inventory_units ALTER COLUMN hold_for SET DEFAULT 0');

        // Update existing null values to 0
        DB::table('local_market_inventory_units')->whereNull('hold_for')->update(['hold_for' => 0]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE local_market_inventory_units ALTER COLUMN hold_for DROP DEFAULT');

    }
};
