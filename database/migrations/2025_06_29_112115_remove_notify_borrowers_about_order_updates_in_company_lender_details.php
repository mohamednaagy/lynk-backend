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
        Schema::table('company_lender_details', function (Blueprint $table) {
            $table->dropColumn('notify_borrowers_about_order_updates');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {}
};
