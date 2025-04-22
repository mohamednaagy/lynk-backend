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
        Schema::table('company_lender_clients', function (Blueprint $table) {
            $table->dropUnique('unique_client_per_company');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('company_lender_clients', function (Blueprint $table) {
            $table->unique(['national_id', 'company_id'], 'unique_client_per_company');
        });
    }
};
