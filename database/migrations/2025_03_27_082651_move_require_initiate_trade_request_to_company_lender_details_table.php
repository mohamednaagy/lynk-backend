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
            $table->boolean('require_initiate_trade_request')
                ->default(false)->after('force_preferred_commodity_type');
        });

        DB::table('companies')->select('id', 'require_initiate_trade_request')->chunkById(1000, function ($companies) {
            $data = $companies->map(fn ($company) => [
                'company_id' => $company->id,
                'require_initiate_trade_request' => $company->require_initiate_trade_request,
            ])->toArray();

            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'], // Unique key to prevent duplicates
                ['require_initiate_trade_request'] // Columns to update
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
            $table->dropColumn('require_initiate_trade_request');
        });
    }
};
