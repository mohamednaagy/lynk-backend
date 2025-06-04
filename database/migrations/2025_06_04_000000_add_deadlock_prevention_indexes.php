<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('local_market_inventories', function (Blueprint $table) {
            // Index for refreshStockQuantities queries to reduce lock scope
            $table->index(['id', 'reserved_items', 'available_quantity'], 'inventory_stock_update_idx');
        });

        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            // Compound index for the queries in completeOrderUnits
            $table->index(['hold_for', 'local_market_inventory_id', 'status'], 'units_completion_idx');

            // Index for ownership changes
            $table->index(['hold_for', 'current_owner', 'current_owner_type'], 'units_ownership_idx');
        });

        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
            // Index for order inventory relationships
            $table->index(['local_market_order_id', 'local_market_inventory_id'], 'order_inventory_relation_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->dropIndex('inventory_stock_update_idx');
        });

        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropIndex('units_completion_idx');
            $table->dropIndex('units_ownership_idx');
        });

        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
            $table->dropIndex('order_inventory_relation_idx');
        });
    }
};
