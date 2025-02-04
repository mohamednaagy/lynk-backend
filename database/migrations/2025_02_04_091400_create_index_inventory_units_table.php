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
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->index(['local_market_inventory_id', 'status', 'deleted_at'], 'idx_lmi_status_deleted');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropIndex('idx_lmi_status_deleted');
        });
    }
};
