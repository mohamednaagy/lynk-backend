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
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->index(['hold_for', 'id'], 'idx_hold_for_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_inventory_units', function (Blueprint $table) {
            $table->dropIndex('idx_hold_for_id');
        });
    }
};
