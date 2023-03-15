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
            $table->string('customer_name')->nullable()->after('status_reason');
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
            $table->dropColumn('customer_name');
        });
    }
};
