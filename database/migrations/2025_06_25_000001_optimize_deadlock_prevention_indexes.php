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
        // 1. Optimize the main inventory table for bulk updates

        // Check if the old index exists before trying to drop it
        $oldIndexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME = 'inventory_stock_update_idx'
            LIMIT 1
        ");

        Schema::table('local_market_inventories', function (Blueprint $table) use ($oldIndexExists) {
            // Drop existing index if it exists
            if (! empty($oldIndexExists)) {
                $table->dropIndex('inventory_stock_update_idx');
            }

            // Check if new indexes don't already exist before creating them
            $pkIndexExists = DB::select("
                SELECT INDEX_NAME 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventories' 
                AND INDEX_NAME = 'idx_inventory_pk_only'
                LIMIT 1
            ");

            $stockFilterIndexExists = DB::select("
                SELECT INDEX_NAME 
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventories' 
                AND INDEX_NAME = 'idx_inventory_stock_filter'
                LIMIT 1
            ");

            // Primary key + frequently updated columns for faster row locks
            if (empty($pkIndexExists)) {
                $table->index(['id'], 'idx_inventory_pk_only');
            }

            // Separate index for queries that filter by stock levels
            if (empty($stockFilterIndexExists)) {
                $table->index(['available_quantity', 'reserved_items', 'status'], 'idx_inventory_stock_filter');
            }
        });

        // 2. Create covering index for COUNT subqueries
        $coveringIndexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME = 'idx_units_count_covering'
            LIMIT 1
        ");

        if (empty($coveringIndexExists)) {
            DB::statement('
                CREATE INDEX idx_units_count_covering 
                ON local_market_inventory_units (local_market_inventory_id, status, deleted_at)
            ');
        }

        // 3. Add optimized compound index for status-based queries
        // MySQL doesn't support partial indexes, so we include status in the index
        $statusIndexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME = 'idx_units_status_optimized'
            LIMIT 1
        ");

        if (empty($statusIndexExists)) {
            DB::statement('
                CREATE INDEX idx_units_status_optimized 
                ON local_market_inventory_units (status, local_market_inventory_id, deleted_at)
            ');
        }

        // 4. Add index specifically for hold_for operations (commonly used)
        // First check if a similar index already exists
        $existingHoldForIndex = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME LIKE '%hold_for%'
            LIMIT 1
        ");

        if (empty($existingHoldForIndex)) {
            DB::statement('
                CREATE INDEX idx_units_hold_for_optimized 
                ON local_market_inventory_units (hold_for, local_market_inventory_id, status)
            ');
        }

        // 5. Optimize for the bulk update WHERE IN clause
        $hashIndexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME IN ('idx_inventory_hash', 'idx_inventory_btree')
            LIMIT 1
        ");

        if (empty($hashIndexExists)) {
            Schema::table('local_market_inventories', function (Blueprint $table) {
                // Add hash index for faster IN operations (if using MySQL 8.0+)
                try {
                    DB::statement('ALTER TABLE local_market_inventories ADD INDEX idx_inventory_hash USING HASH (id)');
                } catch (\Exception $e) {
                    // Fallback for older MySQL versions
                    $table->index(['id'], 'idx_inventory_btree');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Check which indexes exist before trying to drop them
        $existingIndexes = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME IN ('idx_inventory_pk_only', 'idx_inventory_stock_filter', 'idx_inventory_hash', 'idx_inventory_btree')
        ");

        $existingIndexNames = array_column($existingIndexes, 'INDEX_NAME');

        // Drop the indexes we added
        Schema::table('local_market_inventories', function (Blueprint $table) use ($existingIndexNames) {
            if (in_array('idx_inventory_pk_only', $existingIndexNames)) {
                $table->dropIndex('idx_inventory_pk_only');
            }
            if (in_array('idx_inventory_stock_filter', $existingIndexNames)) {
                $table->dropIndex('idx_inventory_stock_filter');
            }
            if (in_array('idx_inventory_hash', $existingIndexNames)) {
                $table->dropIndex('idx_inventory_hash');
            }
            if (in_array('idx_inventory_btree', $existingIndexNames)) {
                $table->dropIndex('idx_inventory_btree');
            }
        });

        // Use DROP INDEX IF EXISTS for MySQL syntax
        DB::statement('DROP INDEX IF EXISTS idx_units_count_covering ON local_market_inventory_units');
        DB::statement('DROP INDEX IF EXISTS idx_units_status_optimized ON local_market_inventory_units');
        DB::statement('DROP INDEX IF EXISTS idx_units_hold_for_optimized ON local_market_inventory_units');

        // Recreate original index only if it doesn't exist
        $originalIndexExists = DB::select("
            SELECT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME = 'inventory_stock_update_idx'
            LIMIT 1
        ");

        if (empty($originalIndexExists)) {
            Schema::table('local_market_inventories', function (Blueprint $table) {
                $table->index(['id', 'reserved_items', 'available_quantity'], 'inventory_stock_update_idx');
            });
        }
    }
};
