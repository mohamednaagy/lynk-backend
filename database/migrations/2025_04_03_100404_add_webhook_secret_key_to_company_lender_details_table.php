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
            $table->text('webhook_secret_key')->nullable();
        });

        // Chunk through the `companies` table in batches
        DB::table('companies')->select('id', 'webhook_secret_key')->chunkById(100, function ($companies) {
            $data = [];

            // Prepare data for updating or inserting into `company_lender_details`
            foreach ($companies as $company) {
                $data[] = [
                    'company_id' => $company->id,
                    'webhook_secret_key' => $company->webhook_secret_key,
                ];
            }

            // Bulk update or insert the data into `company_lender_details`
            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'], // Unique key to prevent duplicates
                ['webhook_secret_key'] // Columns to update
            );
        });

        // TODO: Remove this after Testing
        // Schema::table('companies', function (Blueprint $table) {
        //     $table->dropColumn('webhook_secret_key');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('webhook_secret_key');
        });
    }
};
