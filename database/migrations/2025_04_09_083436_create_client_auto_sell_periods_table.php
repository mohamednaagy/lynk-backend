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
        Schema::create('client_auto_sell_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_lender_client_id');
            $table->foreign('company_lender_client_id')->references('id')->on('company_lender_clients');
            $table->date('effective_start');
            $table->date('effective_end');
            $table->index(['company_lender_client_id', 'effective_start', 'effective_end'], 'client_with_effective_start_and_end');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('client_auto_sell_periods');
    }
};
