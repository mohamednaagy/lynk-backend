<?php

namespace App\Console\Commands;

use App\Jobs\Transaction\GenerateVoucherReceiptForOldTransaction;
use Illuminate\Console\Command;

class GenerateVoucherReceiptForOldTransactionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voucher-receipt:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate voucher receipt for old transactions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch(new GenerateVoucherReceiptForOldTransaction);

        return Command::SUCCESS;
    }
}
