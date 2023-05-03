<?php

namespace App\Console\Commands;

use App\Jobs\Transaction\GenerateVoucherInvoiceForOldTransaction;
use Illuminate\Console\Command;

class GenerateVoucherInvoiceForOldTransactionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voucher-invoice:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate voucher invoice for old transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new GenerateVoucherInvoiceForOldTransaction);

        return Command::SUCCESS;
    }
}
