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
            $table->dropForeign(['supplier_id']);
            $table->dropForeign('fk_lm_inventory');
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
