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
        Transaction::where('reason', TransactionReason::VatPercentageFee)
            ->orderBy('id')
            ->chunk(100, function ($transactions) {
                foreach ($transactions as $vatTransaction) {
                    DB::connection(config('wallet.database.connection'))
                        ->transaction(function () use ($vatTransaction) {
                            $creationFeeTransaction = Transaction::find(
                                $vatTransaction->meta['transaction_id']
                            );

                            $vatTransaction->update([
                                'reference_number' => $creationFeeTransaction->reference_number,
                                'meta->old_reference_number' => $vatTransaction->reference_number,
                            ]);

                            $creationFeeTransaction->update([
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
