<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection;

    public function __construct()
    {
        $this->connection = config('wallet.database.connection', 'mysql');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->uuid()->unique();
            $table->foreignId('from_id')->constrained('wallets');
            $table->foreignId('to_id')->constrained('wallets');
            $table->foreignId('deposit_id')->constrained('transactions');
            $table->foreignId('withdraw_id')->constrained('transactions');
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
        Schema::dropIfExists('transfers');
    }
};
