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
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->json('previous_owners')->nullable()->after('hold_for');

            // Add index for JSON search
            DB::statement('CREATE INDEX idx_previous_owners ON local_market_inventory_units ((CAST(previous_owners AS CHAR(512))))');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            // Drop the index first
            DB::statement('DROP INDEX idx_previous_owners ON local_market_inventory_units');

            $table->dropColumn('previous_owners');
        });
    }
};
