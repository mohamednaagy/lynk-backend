<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {
            $foreignKeys = [
                'supplier_id',
                'fk_lm_inventory',
                'local_market_order_has_inventories_supplier_id_foreign',
                'local_market_order_has_inventories_inventory_id_foreign'
            ];

            foreach ($foreignKeys as $fk) {
                $exists = DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'local_market_order_has_inventories')
                ->where('CONSTRAINT_NAME', $fk)
                ->exists();

                if ($exists) {
                    $table->dropForeign($fk);
                }
            }

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_order_has_inventories', function (Blueprint $table) {

            $table->foreign('local_market_inventory_id', 'fk_lm_inventory')
                ->references('id')
                ->on('local_market_inventories');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('companies');
        });
    }
};
