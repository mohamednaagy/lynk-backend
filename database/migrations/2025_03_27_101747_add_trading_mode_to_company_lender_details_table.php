<?php

use App\Enums\TraderOrderMode;
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
            $table->string('trading_mode')->default(TraderOrderMode::Automatic);
        });

        // Chunk through the `companies` table in batches
        DB::table('companies')->select('id', 'trading_mode')->chunkById(100, function ($companies) {
            $data = [];

            // Prepare data for updating or inserting into `company_lender_details`
            foreach ($companies as $company) {
                $data[] = [
                    'company_id' => $company->id,
                    'trading_mode' => $company->trading_mode,
                ];
            }

            // Bulk update or insert the data into `company_lender_details`
            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'], // Unique key to prevent duplicates
                ['trading_mode'] // Columns to update
            );
        });

        // TODO: Remove this after Testing
        // Schema::table('companies', function (Blueprint $table) {
        //     $table->dropColumn('trading_mode');
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
            $table->dropColumn('trading_mode');
        });
    }
};
