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
            $table->smallInteger('contract_signed_type')->default(\App\Enums\ContractSignedType::Sell)->comment('Sell=>2|Deliver=>');
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
            $table->dropColumn('contract_signed_type');
        });
    }
};
