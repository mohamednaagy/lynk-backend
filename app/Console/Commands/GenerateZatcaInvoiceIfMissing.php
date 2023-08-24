<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Jobs\GenerateZatcaInvoiceIfMissing as JobsGenerateZatcaInvoiceIfMissing;
use App\Models\Transaction;
use Illuminate\Console\Command;

class GenerateZatcaInvoiceIfMissing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vat-invoices:gen-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate zatca invoices if missing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Transaction::query()
            ->where('reason', TransactionReason::OrderCreationFee)
            ->orderBy('id')
            ->chunk(100, function ($transactions) {
                $transactions->each(function ($transaction) {
                    JobsGenerateZatcaInvoiceIfMissing::dispatch($transaction);
                });
            });

        return Command::SUCCESS;
    }
}
