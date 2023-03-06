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
        Schema::dropColumns('financing_orders', ['client_wakala_accepted_at']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropColumns('trader_orders', ['client_wakala_accepted_at']);
        Schema::table('financing_orders', function (Blueprint $table) {
            $table->timestamp('client_wakala_accepted_at')->nullable()->after('is_verification_required');
        });
    }
};
