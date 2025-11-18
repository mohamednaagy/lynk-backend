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
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->decimal('cost_with_vat', 64, 0)->default(0)->after('amount');
            $table->decimal('cost_without_vat', 64, 0)->default(0)->after('cost_with_vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('cost_with_vat');
            $table->dropColumn('cost_without_vat');
        });
    }
};
