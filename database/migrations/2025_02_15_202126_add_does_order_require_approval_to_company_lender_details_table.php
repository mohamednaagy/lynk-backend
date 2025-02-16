<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->boolean('does_order_require_approval')
                ->default(false)->after('preferred_market_type');
        });

        // Migrate data from `companies.does_order_require_approval` to `company_lender_details`
        DB::table('companies')->select('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('company_lender_details')->updateOrInsert(
                    ['company_id' => $company->id],
                    [
                        'does_order_require_approval' => DB::table('companies')
                            ->where('id', $company->id)
                            ->value('does_order_require_approval'),
                    ]
                );
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('does_order_require_approval');
        });
    }
};
