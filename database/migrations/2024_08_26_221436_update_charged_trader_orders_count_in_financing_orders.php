<?php

use App\Models\FinancingOrder;
use App\Models\Transaction;
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
            $table->tinyInteger('update_charged_count_status')->default(0)->comment("PENDING=>0|ERROR=>1|DONE=>2");
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
            $table->dropColumn('update_charged_count_status');
        });
    }
};
