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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('ALTER TABLE local_market_inventory_units ADD COLUMN partition_id BIGINT UNSIGNED AS (local_market_inventory_id) STORED;');
        DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id, partition_id);');
        DB::statement('ALTER TABLE local_market_inventory_units PARTITION BY Hash (partition_id) PARTITIONS 8;');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE local_market_inventory_units DROP PRIMARY KEY, ADD PRIMARY KEY (id);');
        DB::statement('ALTER TABLE local_market_inventory_units DROP PARTITION BY Hash (partition_id);');
        DB::statement('ALTER TABLE local_market_inventory_units DROP COLUMN partition_id;');

    }
};
