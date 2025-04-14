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
            $table->boolean('auto_complete_sell')
                ->default(false)
                ->after('type');
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
            $table->dropColumn('auto_complete_sell');
        });
    }
};
