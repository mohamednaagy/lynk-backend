<?php

namespace App\Jobs\Transaction;

use App\Actions\Contracts\Wallets\GenerateVoucherReceipt;
use App\Enums\MediaCollections\TransactionMediaCollection;
use App\Enums\TransactionReason;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateVoucherReceiptForOldTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Transaction::query()
            ->whereIn('reason', [TransactionReason::DepositByEdaat, TransactionReason::ManualDeposit])
            ->orderBy('id')
            ->chunk(100, function ($transactions) {
                $transactions->each(function ($transaction) {
                    if ($transaction->wallet->holder === null) {
                        return;
                    }

                    if ($transaction->hasMedia(TransactionMediaCollection::VoucherReceipt)) {
                        $transaction->clearMediaCollection(TransactionMediaCollection::VoucherReceipt);
                    }

                    app(GenerateVoucherReceipt::class)->handle($transaction);
                });
            });
    }
}
