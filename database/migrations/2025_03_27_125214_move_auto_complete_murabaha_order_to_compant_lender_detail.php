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
            $table->boolean('auto_complete_murabaha_order')
                ->default(false)->after('public_status_comment');
        });

        DB::table('companies')->select('id', 'auto_complete_murabaha_order')->chunkById(1000, function ($companies) {
            $data = $companies->map(fn ($company) => [
                'company_id' => $company->id,
                'auto_complete_murabaha_order' => $company->auto_complete_murabaha_order,
            ])->toArray();

            DB::table('company_lender_details')->upsert(
                $data,
                ['company_id'],
                ['auto_complete_murabaha_order']
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
            $table->dropColumn('auto_complete_murabaha_order');
        });
    }
};
