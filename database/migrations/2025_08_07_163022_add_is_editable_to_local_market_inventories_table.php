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
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->boolean('is_editable')
                ->after('status')
                ->default(1)
                ->comment('Indicates whether this inventory is editable during purchasing operations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('local_market_inventories', function (Blueprint $table) {
            $table->dropColumn('is_editable');
        });
    }
};
