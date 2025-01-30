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
        Schema::create('loan_coverage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_market_order_id')->constrained();
            $table->decimal('loan', 64, 0);
            $table->string('status');
            $table->string('elapsed_time');
            $table->text('selected_inventories')->nullable();
            $table->text('suggested_inventories')->nullable();
            $table->string('strategy');
            $table->string('error_msg', 2000)->nullable();
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
        Schema::dropIfExists('loan_coverage_histories');
    }
};
