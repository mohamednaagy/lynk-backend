<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Drop Foreign Keys if They Exist
        $foreignKeys = [
            'local_market_inventory_units' => [
                'inventory_units_commodity_item_id_foreign',
                'inventory_units_local_market_inventory_id_foreign',
                'local_market_inventory_units_last_completed_order_id_foreign',
            ],
            'local_market_order_has_units' => [
                'local_market_order_has_units_unit_id_foreign',
            ],
            'local_market_unit_ownership' => [
                'local_market_unit_ownership_unit_id_foreign',
            ],
        ];

        foreach ($foreignKeys as $table => $keys) {
            foreach ($keys as $key) {
                try {
                    DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$key`;");
                } catch (\Exception $e) {
                    // Ignore if foreign key does not exist
                }
            }
        }

        // Step 2: Modify Table Structure (Ensure No Existing Partitions)
        try {
            DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id, local_market_inventory_id);');
            DB::statement('ALTER TABLE local_market_inventory_units PARTITION BY HASH (local_market_inventory_id) PARTITIONS 8;');
        } catch (\Exception $e) {
            // Handle error if partitioning fails
        }

        // Step 3: Add Foreign Keys if Not Existing
        if (! in_array(env('APP_ENV'), ['dev', 'sandbox', 'local'])) {
            $foreignKeyQueries = [
                'local_market_inventory_units' => [
                    'inventory_units_commodity_item_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_commodity_item_id_foreign FOREIGN KEY (commodity_item_id) REFERENCES commodity_items (id) ON DELETE CASCADE;',
                    'inventory_units_local_market_inventory_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_local_market_inventory_id_foreign FOREIGN KEY (local_market_inventory_id) REFERENCES local_market_inventories (id) ON DELETE CASCADE;',
                    'local_market_inventory_units_last_completed_order_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT local_market_inventory_units_last_completed_order_id_foreign FOREIGN KEY (last_completed_order_id) REFERENCES local_market_orders (id);',
                ],
                'local_market_order_has_units' => [
                    'local_market_order_has_units_unit_id_foreign' => 'ALTER TABLE local_market_order_has_units ADD CONSTRAINT local_market_order_has_units_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;',
                ],
                'local_market_unit_ownership' => [
                    'local_market_unit_ownership_unit_id_foreign' => 'ALTER TABLE local_market_unit_ownership ADD CONSTRAINT local_market_unit_ownership_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;',
                ],
            ];

            foreach ($foreignKeyQueries as $table => $keys) {
                foreach ($keys as $key => $query) {
                    $exists = DB::select('
                        SELECT CONSTRAINT_NAME
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                        WHERE TABLE_NAME = ?
                        AND CONSTRAINT_NAME = ?
                        AND TABLE_SCHEMA = DATABASE()
                    ', [$table, $key]);

                    if (empty($exists)) {
                        DB::statement($query);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Step 1: Drop Foreign Keys Added in `up()`
        $foreignKeys = [
            'local_market_inventory_units' => [
                'inventory_units_commodity_item_id_foreign',
                'inventory_units_local_market_inventory_id_foreign',
                'local_market_inventory_units_last_completed_order_id_foreign',
            ],
            'local_market_order_has_units' => [
                'local_market_order_has_units_unit_id_foreign',
            ],
            'local_market_unit_ownership' => [
                'local_market_unit_ownership_unit_id_foreign',
            ],
        ];

        foreach ($foreignKeys as $table => $keys) {
            foreach ($keys as $key) {
                try {
                    DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$key`;");
                } catch (\Exception $e) {
                    Log::error('Error dropping foreign key: '.$e->getMessage());
                    throw $e;
                }
            }
        }

        // Step 2: Drop Partitioning (Recreate Table)
        try {
            DB::statement('ALTER TABLE local_market_inventory_units DROP PARTITIONING;');
        } catch (\Exception $e) {
            Log::error('Error partitioning units table: '.$e->getMessage());
            throw $e;
        }

        // Step 3: Restore Original Primary Key
        try {
            DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id);');
        } catch (\Exception $e) {
            Log::error('Error restoring primary key: '.$e->getMessage());
            throw $e;
        }

        // Step 4: Re-add Original Foreign Keys
        $originalForeignKeys = [
            'local_market_inventory_units' => [
                'inventory_units_commodity_item_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_commodity_item_id_foreign FOREIGN KEY (commodity_item_id) REFERENCES commodity_items (id) ON DELETE CASCADE;',
                'inventory_units_local_market_inventory_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_local_market_inventory_id_foreign FOREIGN KEY (local_market_inventory_id) REFERENCES local_market_inventories (id) ON DELETE CASCADE;',
                'local_market_inventory_units_last_completed_order_id_foreign' => 'ALTER TABLE local_market_inventory_units ADD CONSTRAINT local_market_inventory_units_last_completed_order_id_foreign FOREIGN KEY (last_completed_order_id) REFERENCES local_market_orders (id);',
            ],
            'local_market_order_has_units' => [
                'local_market_order_has_units_unit_id_foreign' => 'ALTER TABLE local_market_order_has_units ADD CONSTRAINT local_market_order_has_units_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;',
            ],
            'local_market_unit_ownership' => [
                'local_market_unit_ownership_unit_id_foreign' => 'ALTER TABLE local_market_unit_ownership ADD CONSTRAINT local_market_unit_ownership_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;',
            ],
        ];

        foreach ($originalForeignKeys as $table => $keys) {
            foreach ($keys as $key => $query) {
                $exists = DB::select('
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = ?
                AND CONSTRAINT_NAME = ?
                AND TABLE_SCHEMA = DATABASE()
            ', [$table, $key]);

                if (empty($exists)) {
                    DB::statement($query);
                }
            }
        }
    }
};
