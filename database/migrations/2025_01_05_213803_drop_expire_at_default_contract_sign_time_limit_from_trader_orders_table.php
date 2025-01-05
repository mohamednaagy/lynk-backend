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
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->dropColumn('expire_at');
            $table->dropColumn('default_contract_sign_time_limit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_orders', function (Blueprint $table) {
            $table->unsignedInteger('default_contract_sign_time_limit')->nullable()->comment('Default measurement unit (minutes)')->after('version');
            $table->dateTime('expire_at')->nullable()->after('default_contract_sign_time_limit');
        });
    }
};
