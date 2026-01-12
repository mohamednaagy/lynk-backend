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
        Schema::table('tiered_pricing', function (Blueprint $table) {
            $table->decimal('vat_amount', 64, 0)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tiered_pricing', function (Blueprint $table) {
            $table->decimal('vat_amount', 64, 0)->change();
        });
    }
};
