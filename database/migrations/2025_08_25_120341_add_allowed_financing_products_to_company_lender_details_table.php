<?php

use App\Enums\FinancingOrderTypeEnum;
use App\Models\CompanyLenderDetail;
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
            $table->json('allowed_financial_order_types')->after('token_version');
        });

        CompanyLenderDetail::query()->update([
            'allowed_financial_order_types' => json_encode([
                FinancingOrderTypeEnum::NormalLending
            ])
        ]);

        Schema::table('financing_orders', function (Blueprint $table) {
            $table->tinyInteger('type')->default(FinancingOrderTypeEnum::NormalLending)->after('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('allowed_financial_order_types');
        });

        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
