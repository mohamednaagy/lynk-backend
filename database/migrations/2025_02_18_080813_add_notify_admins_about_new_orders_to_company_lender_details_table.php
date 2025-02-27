<?php

use App\Enums\CompanyNewOrderNotificationForAdminStatus;
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
            $table->tinyInteger('notify_admins_about_new_orders')
                ->default(CompanyNewOrderNotificationForAdminStatus::On)
                ->after('does_order_require_approval');
        });

        // Migrate data from `companies.notify_admins_about_new_orders` to `company_lender_details`
        DB::table('companies')->select('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('company_lender_details')->updateOrInsert(
                    ['company_id' => $company->id],
                    [
                        'notify_admins_about_new_orders' => DB::table('companies')
                            ->where('id', $company->id)
                            ->value('notify_admins_about_new_orders'),
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
            $table->dropColumn('notify_admins_about_new_orders');
        });
    }
};
