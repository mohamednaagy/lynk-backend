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
        Schema::dropIfExists('transfers');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->uuid()->unique();
            $table->unsignedBigInteger('wallet_id');
            $table->string('reference_number');
            $table->decimal('amount', 64);
            $table->json('data');

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
        Schema::dropIfExists('transactions');
    }
};
