<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a composite index to optimize the getUnitsByGroupedByPreviousOwner query
     * which filters by hold_for, groups by local_market_inventory_id and previous_owner_type,
     * and joins on previous_owner.
     */
    public function up(): void
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->index(
                ['hold_for', 'local_market_inventory_id', 'previous_owner_type', 'previous_owner'],
                'idx_units_previous_owner_grouping'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropIndex('idx_units_previous_owner_grouping');
        });
    }
};
