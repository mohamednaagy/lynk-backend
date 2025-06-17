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
        // Optimize wallets table
        Schema::connection('wallet')->table('wallets', function (Blueprint $table) {
            // Composite index for wallet lookup
            $table->index(['name', 'holder_id', 'holder_type'], 'wallets_name_holder_idx');
        });

        // Optimize transactions table
        Schema::connection('wallet')->table('transactions', function (Blueprint $table) {
            $table->index('wallet_id', 'transactions_wallet_id_idx');
            $table->index('reference_number', 'transactions_reference_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('wallet')->table('wallets', function (Blueprint $table) {
            $table->dropIndex('wallets_name_holder_idx');
        });
        Schema::connection('wallet')->table('transactions', function (Blueprint $table) {
            $table->dropIndex('transactions_wallet_id_idx');
            $table->dropIndex('transactions_reference_number_idx');
        });
    }
};
