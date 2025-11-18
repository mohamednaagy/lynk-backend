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
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $table) {
            // Drop the foreign key constraint on wallet_id
            $table->dropForeign(['wallet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('transactions', function (Blueprint $table) {
            // Re-add the foreign key constraint on wallet_id
            $table->foreign('wallet_id')->references('id')->on('wallets');
        });
    }
};
