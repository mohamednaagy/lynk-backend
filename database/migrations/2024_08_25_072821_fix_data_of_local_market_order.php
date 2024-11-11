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
        if (! Schema::hasColumn('local_market_orders', 'reference')) {
            Schema::table('local_market_orders', function (Blueprint $table) {
                $table->string('reference');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('local_market_orders', 'reference')) {
            Schema::table('local_market_orders', function (Blueprint $table) {
                $table->dropColumn('reference');
            });
        }
    }
};
