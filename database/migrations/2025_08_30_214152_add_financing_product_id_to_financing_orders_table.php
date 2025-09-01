<?php

use App\Enums\FinancingProductEnum;
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
            $table->integer('financing_product_id')->default(FinancingProductEnum::NormalLending)->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('financing_product_id');
        });
    }
};
