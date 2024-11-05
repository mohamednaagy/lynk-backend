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
        DB::table('trader_orders')->update([
            'default_contract_sign_time_limit' => DB::raw('default_contract_sign_time_limit * 60')
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('trader_orders')->update([
            'default_contract_sign_time_limit' => DB::raw('default_contract_sign_time_limit / 60')
        ]);
    }
};
