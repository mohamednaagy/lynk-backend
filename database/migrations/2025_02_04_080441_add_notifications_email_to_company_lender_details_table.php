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
            $table->string('notifications_email')->after('name')->nullable();
        });

        // Migrate data from `companies.notifications_email` to `company_lender_details`
        DB::table('companies')->select('id')->chunkById(100, function ($companies) {
            foreach ($companies as $company) {
                DB::table('company_lender_details')->updateOrInsert(
                    ['company_id' => $company->id],
                    [
                        'notifications_email' => DB::table('companies')
                            ->where('id', $company->id)
                            ->value('notifications_email'),
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
            $table->dropColumn('notifications_email');
        });
    }
};
