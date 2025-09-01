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
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->string('allowed_financing_products')->default(FinancingProductEnum::NormalLending)->after('token_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('allowed_financing_products');
        });
    }
};
