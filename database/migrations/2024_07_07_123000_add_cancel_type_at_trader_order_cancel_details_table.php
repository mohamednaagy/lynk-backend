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
        Schema::table('trader_order_cancel_details', function (Blueprint $table) {
            $table->unsignedTinyInteger('cancel_type')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable()->after('id')->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trader_order_cancel_details', function (Blueprint $table) {
            $table->dropColumn('cancel_type');
        });
    }
};
