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
            $table->dropColumn('force_commodity_type');
        });

        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->dropColumn('force_commodity_type');
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
            $table->boolean('force_commodity_type')->default(false)->after('commodity_type_id')->comment('Force using a specific commodity type from trader, financing, or company');
        });

        Schema::table('local_market_orders', function (Blueprint $table) {
            $table->boolean('force_commodity_type')->default(false)->after('preferred_commodity_type')->comment('Force using a specific commodity type from trader, financing, or company');
        });
    }
};
