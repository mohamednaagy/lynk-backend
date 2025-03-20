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

        // Chunk through the `companies` table in batches
        DB::table('companies')->select('id', 'notify_borrowers_about_order_updates')->chunkById(100, function ($companies) {
            $data = [];

            // Prepare data for updating or inserting into `company_lender_details`
            foreach ($companies as $company) {
                $data[] = [
                    'company_id' => $company->id,
                    'notify_borrowers_about_order_updates' => $company->notify_borrowers_about_order_updates,
                ];
            }

            // Bulk update or insert the data into `company_lender_details`
            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'], // Unique key to prevent duplicates
                ['notify_borrowers_about_order_updates'] // Columns to update
            );
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
