<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            'default_contract_sign_time_limit' => DB::raw('default_contract_sign_time_limit * 60'),
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
            'default_contract_sign_time_limit' => DB::raw('default_contract_sign_time_limit / 60'),
        ]);
    }
};
