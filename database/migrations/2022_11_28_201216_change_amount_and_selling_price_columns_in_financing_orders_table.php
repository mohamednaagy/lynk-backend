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
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->decimal('amount', 64, 0)->change();
            $table->decimal('selling_price', 64, 0)->change();
            $table->string('currency', 4)->after('amount')->default(config('money.defaultCurrency'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->double('amount')->change();
            $table->double('selling_price')->change();
            $table->dropColumn('currency');
        });
    }
};
