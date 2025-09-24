<?php

use App\Enums\FinancingOrderBorrowerTypeEnum;
use App\Enums\FinancingOrderLenderTypeEnum;
use App\Models\FinancingOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->tinyInteger('lender_type')
                ->after('status_reason');

            $table->string('lender_identifier', 50)
                ->after('lender_type');

            $table->tinyInteger('borrower_type')
                ->after('lender_identifier');

            $table->renameColumn('customer_name', 'borrower_identifier');
        });

        FinancingOrder::query()->update([
            'lender_type' => FinancingOrderLenderTypeEnum::NormalLending,
            'lender_identifier' => DB::raw('company_id'),
            'borrower_type' => FinancingOrderBorrowerTypeEnum::Customer,
        ]);

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->dropColumn('lender_type');
            $table->dropColumn('lender_identifier');
            $table->dropColumn('borrower_type');
            $table->renameColumn('borrower_identifier', 'customer_name');
        });
    }
};
