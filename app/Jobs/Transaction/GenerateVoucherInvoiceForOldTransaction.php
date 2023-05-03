<?php

namespace App\Jobs\Transaction;

use App\Actions\Contracts\Wallets\GenerateVoucherInvoice;
use App\Enums\TransactionReason;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateVoucherInvoiceForOldTransaction implements ShouldQueue
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
            ->orderBy('created_at')
            ->chunk(100, function ($transactions) {
                $transactions->each(function ($transaction) {
                    app(GenerateVoucherInvoice::class)->handle($transaction);
                });
            });
    }
}
