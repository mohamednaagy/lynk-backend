<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiered_pricing', function (Blueprint $table) {
            $table->renameColumn('order_cost_without_vat', 'temp_order_cost_without_vat');
        });

        Schema::table('tiered_pricing', function (Blueprint $table) {
            $table->decimal('temp_order_cost_without_vat', 64, 0)->nullable()->change();
            $table->decimal('order_cost_without_vat', 64, 0)->after('temp_order_cost_without_vat');
        });
    }

    public function down(): void
    {
        Schema::table('tiered_pricing', function (Blueprint $table) {
            $table->dropColumn('order_cost_without_vat');
            $table->renameColumn('temp_order_cost_without_vat', 'order_cost_without_vat');
        });
    }
};
