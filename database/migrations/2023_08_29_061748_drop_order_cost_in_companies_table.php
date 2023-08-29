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
            $table->dropColumn(['order_cost', 'order_cost_currency']);
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
            $table->decimal('order_cost', 64, 0)->default(Money::parseByDecimal(150)->getAmount());
            $table->string('order_cost_currency', 4)->default(Money::getDefaultCurrency());
        });
    }
};
