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
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->decimal('amount', 64, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->decimal('amount', 64, 0)->change();
        });
    }
};
