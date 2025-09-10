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
        Schema::table('local_market_eligible_quantities', function (Blueprint $table) {
            $table->unsignedBigInteger('touched_by')->nullable()->after('eligible_quantity')->comment('Helper column for managing inventory lock and release during find-and-hold operations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_eligible_quantities', function (Blueprint $table) {
            $table->dropColumn('touched_by');
        });
    }
};
