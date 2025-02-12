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
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY inventory_units_commodity_item_id_foreign;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY inventory_units_local_market_inventory_id_foreign;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY local_market_inventory_units_last_completed_order_id_foreign;');
        DB::statement('ALTER TABLE local_market_order_has_units DROP FOREIGN KEY local_market_order_has_units_unit_id_foreign;');
        DB::statement('ALTER TABLE local_market_unit_ownership DROP FOREIGN KEY local_market_unit_ownership_unit_id_foreign;');

        DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id, local_market_inventory_id);');
        DB::statement('ALTER TABLE local_market_inventory_units PARTITION BY HASH (local_market_inventory_id) PARTITIONS 8;');

        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_commodity_item_id_foreign FOREIGN KEY (commodity_item_id) REFERENCES commodity_items (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_local_market_inventory_id_foreign FOREIGN KEY (local_market_inventory_id) REFERENCES local_market_inventories (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT local_market_inventory_units_last_completed_order_id_foreign FOREIGN KEY (last_completed_order_id) REFERENCES local_market_orders (id);');
        DB::statement('ALTER TABLE local_market_order_has_units ADD CONSTRAINT local_market_order_has_units_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_unit_ownership ADD CONSTRAINT local_market_unit_ownership_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE local_market_unit_ownership DROP FOREIGN KEY local_market_unit_ownership_unit_id_foreign;');
        DB::statement('ALTER TABLE local_market_order_has_units DROP FOREIGN KEY local_market_order_has_units_unit_id_foreign;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY local_market_inventory_units_last_completed_order_id_foreign;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY inventory_units_local_market_inventory_id_foreign;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP FOREIGN KEY inventory_units_commodity_item_id_foreign;');

        DB::statement('ALTER TABLE local_market_inventory_units REMOVE PARTITIONING;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id);');

        DB::statement('ALTER TABLE local_market_unit_ownership ADD CONSTRAINT local_market_unit_ownership_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_order_has_units ADD CONSTRAINT local_market_order_has_units_unit_id_foreign FOREIGN KEY (unit_id) REFERENCES local_market_inventory_units (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT local_market_inventory_units_last_completed_order_id_foreign FOREIGN KEY (last_completed_order_id) REFERENCES local_market_orders (id);');
        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_local_market_inventory_id_foreign FOREIGN KEY (local_market_inventory_id) REFERENCES local_market_inventories (id) ON DELETE CASCADE;');
        DB::statement('ALTER TABLE local_market_inventory_units ADD CONSTRAINT inventory_units_commodity_item_id_foreign FOREIGN KEY (commodity_item_id) REFERENCES commodity_items (id) ON DELETE CASCADE;');
    }
};
