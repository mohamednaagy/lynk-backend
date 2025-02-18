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
            $table->boolean('notify_borrowers_about_order_updates')
                ->default(false)
                ->after('notify_admins_about_new_orders');
        });

        // Migrate data from `companies.notify_borrowers_about_order_updates` to `company_lender_details`
        DB::table('companies')->select('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('company_lender_details')->updateOrInsert(
                    ['company_id' => $company->id],
                    [
                        'notify_borrowers_about_order_updates' => DB::table('companies')
                            ->where('id', $company->id)
                            ->value('notify_borrowers_about_order_updates'),
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
            $table->dropColumn('notify_borrowers_about_order_updates');
        });
    }
};
