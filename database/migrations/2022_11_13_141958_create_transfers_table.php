<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection;

    public function __construct()
    {
        $this->connection = Config::get('wallet.database.connection');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('transfers');
        Schema::enableForeignKeyConstraints();

        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('from_id')->constrained('wallets');
            $table->foreignId('to_id')->constrained('wallets');
            $table->foreignId('deposit_id')->constrained('transactions');
            $table->foreignId('withdraw_id')->constrained('transactions');
            $table->decimal('amount', 64, 0);
            $table->string('currency', 4);
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
