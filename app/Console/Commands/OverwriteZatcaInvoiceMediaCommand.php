<?php

namespace App\Console\Commands;

use App\Jobs\OverwriteZatcaInvoiceMediaJob;
use Illuminate\Console\Command;

class OverwriteZatcaInvoiceMediaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vat-invoices:regenerate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrite old Zatca invoice with correct data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        dispatch_sync(new OverwriteZatcaInvoiceMediaJob);

        return Command::SUCCESS;
    }
}
