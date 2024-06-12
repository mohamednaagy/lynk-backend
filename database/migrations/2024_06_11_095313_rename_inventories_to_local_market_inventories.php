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
        // Renaming the table
        Schema::rename('inventories', 'local_market_inventories');
        Schema::table('inventory_units', function (Blueprint $table) {
            $table->dropForeign('inventory_units_inventory_id_foreign');
            $table->renameColumn('inventory_id', 'local_market_inventory_id');

            $table->foreign('local_market_inventory_id')
            ->references('id')
                ->on('local_market_inventories')
                ->onDelete('cascade');
        });
        Schema::rename('inventory_units', 'local_market_inventory_units');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropForeign(['local_market_inventory_id']);
            $table->renameColumn('local_market_inventory_id', 'inventory_id');

            $table->foreign('inventory_id')
                ->references('id')
                ->on('inventories')
                ->onDelete('cascade');
        });

        // Renaming the table back to the old name
        Schema::rename('local_market_inventories', 'inventories');
        Schema::rename('local_market_inventory_units', 'inventory_units');
    }
};
