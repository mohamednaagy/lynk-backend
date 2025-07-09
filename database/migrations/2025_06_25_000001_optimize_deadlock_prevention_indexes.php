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
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME = 'inventory_stock_update_idx'
        ");

        Schema::table('local_market_inventories', function (Blueprint $table) use ($oldIndexExists) {
            // Drop existing index if it exists
            if ($oldIndexExists[0]->count > 0) {
                $table->dropIndex('inventory_stock_update_idx');
            }

            // Check if new indexes don't already exist before creating them
            $pkIndexExists = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventories' 
                AND INDEX_NAME = 'idx_inventory_pk_only'
            ");

            $stockFilterIndexExists = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventories' 
                AND INDEX_NAME = 'idx_inventory_stock_filter'
            ");

            // Primary key + frequently updated columns for faster row locks
            if ($pkIndexExists[0]->count == 0) {
                $table->index(['id'], 'idx_inventory_pk_only');
            }

            // Separate index for queries that filter by stock levels
            if ($stockFilterIndexExists[0]->count == 0) {
                $table->index(['available_quantity', 'reserved_items', 'status'], 'idx_inventory_stock_filter');
            }
        });

        // 2. Create covering index for COUNT subqueries
        $coveringIndexExists = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME = 'idx_units_count_covering'
        ");

        if ($coveringIndexExists[0]->count == 0) {
            try {
                DB::statement('
                    CREATE INDEX idx_units_count_covering 
                    ON local_market_inventory_units (local_market_inventory_id, status, deleted_at)
                ');
            } catch (\Exception $e) {
                // Index might already exist, ignore the error
                if (! str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }

        // 3. Add optimized compound index for status-based queries
        // MySQL doesn't support partial indexes, so we include status in the index
        $statusIndexExists = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME = 'idx_units_status_optimized'
        ");

        if ($statusIndexExists[0]->count == 0) {
            try {
                DB::statement('
                    CREATE INDEX idx_units_status_optimized 
                    ON local_market_inventory_units (status, local_market_inventory_id, deleted_at)
                ');
            } catch (\Exception $e) {
                // Index might already exist, ignore the error
                if (! str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }

        // 4. Add index specifically for hold_for operations (commonly used)
        // First check if a similar index already exists
        $existingHoldForIndex = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventory_units' 
            AND INDEX_NAME = 'idx_units_hold_for_optimized'
        ");

        if ($existingHoldForIndex[0]->count == 0) {
            try {
                DB::statement('
                    CREATE INDEX idx_units_hold_for_optimized 
                    ON local_market_inventory_units (hold_for, local_market_inventory_id, status)
                ');
            } catch (\Exception $e) {
                // Index might already exist, ignore the error
                if (! str_contains($e->getMessage(), 'Duplicate key name')) {
                    throw $e;
                }
            }
        }

        // 5. Optimize for the bulk update WHERE IN clause
        $hashIndexExists = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME IN ('idx_inventory_hash', 'idx_inventory_btree')
        ");

        if ($hashIndexExists[0]->count == 0) {
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
        $indexesToCheck = ['idx_inventory_pk_only', 'idx_inventory_stock_filter', 'idx_inventory_hash', 'idx_inventory_btree'];
        $existingIndexNames = [];

        foreach ($indexesToCheck as $indexName) {
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventories' 
                AND INDEX_NAME = ?
            ", [$indexName]);

            if ($indexExists[0]->count > 0) {
                $existingIndexNames[] = $indexName;
            }
        }

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

        // Drop indexes from units table using MySQL-compatible syntax
        $unitsIndexesToDrop = ['idx_units_count_covering', 'idx_units_status_optimized', 'idx_units_hold_for_optimized'];

        foreach ($unitsIndexesToDrop as $indexName) {
            $indexExists = DB::select("
                SELECT COUNT(*) as count
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'local_market_inventory_units' 
                AND INDEX_NAME = ?
            ", [$indexName]);

            if ($indexExists[0]->count > 0) {
                try {
                    DB::statement("ALTER TABLE local_market_inventory_units DROP INDEX {$indexName}");
                } catch (\Exception $e) {
                    // Index might not exist, ignore the error
                    if (! str_contains($e->getMessage(), 'check that column/key exists')) {
                        throw $e;
                    }
                }
            }
        }

        // Recreate original index only if it doesn't exist
        $originalIndexExists = DB::select("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'local_market_inventories' 
            AND INDEX_NAME = 'inventory_stock_update_idx'
        ");

        if ($originalIndexExists[0]->count == 0) {
            Schema::table('local_market_inventories', function (Blueprint $table) {
                $table->index(['id', 'reserved_items', 'available_quantity'], 'inventory_stock_update_idx');
            });
        }
    }
};
