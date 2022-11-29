<?php

use Cknow\Money\Money;
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
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('order_cost', 64, 0)->change();
            $table->string('order_cost_currency', 4)->after('order_cost')->default(Money::getDefaultCurrency());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('order_cost', 64, 2)->change();
            $table->dropColumn('order_cost_currency');
        });
    }
};
