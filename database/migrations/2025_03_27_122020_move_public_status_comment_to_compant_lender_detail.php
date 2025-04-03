<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->text('public_status_comment')->nullable()->after('require_initiate_trade_request');
        });

        DB::table('companies')->select('id', 'public_status_comment')->chunkById(1000, function ($companies) {
            $data = $companies->map(fn ($company) => [
                'company_id' => $company->id,
                'public_status_comment' => $company->public_status_comment,
            ])->toArray();

            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'], // Unique key to prevent duplicates
                ['public_status_comment'] // Columns to update
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
            $table->dropColumn('public_status_comment');
        });
    }
};
