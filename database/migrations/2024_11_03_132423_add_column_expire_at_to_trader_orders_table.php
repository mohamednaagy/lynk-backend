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
            $table->timestamp('expire_at')->nullable()->after('default_contract_sign_time_limit');
            $table->integer('default_contract_sign_time_limit')->nullable()->comment('Default measurement unit (minutes)')->change();

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
            $table->integer('default_contract_sign_time_limit')->nullable()->comment('default measurement unit (hours)')->change();
            $table->dropColumn('expire_at');
        });
    }
};
