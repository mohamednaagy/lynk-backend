<?php

use App\Enums\TransactionReason;
use App\Models\Transaction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Transaction::whereIn('reason', [TransactionReason::DepositByEdaat, TransactionReason::ManualDeposit])
            ->orderBy('id')
            ->chunk(100, function ($transactions) {
                foreach ($transactions as $transaction) {
                    DB::connection(config('wallet.database.connection'))
                        ->transaction(function () use ($transaction) {

                            $transaction->update([
                                'meta->is_vat_included' => false,
                            ]);
                        });
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
