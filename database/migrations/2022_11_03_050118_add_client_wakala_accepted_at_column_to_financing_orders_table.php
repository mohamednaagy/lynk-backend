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
            $table->timestamp('client_wakala_accepted_at')->after('status_reason')->nullable();
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
            $table->dropColumn('client_wakala_accepted_at');
        });
    }
};
