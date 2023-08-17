<?php

namespace App\Console\Commands;

use App\Jobs\General\ProcessRetrieveOrderCertificates;
use App\Models\TraderOrder;
use Illuminate\Console\Command;

class BursamRetrieveOrderCertificatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bursam:retrieve-certs {trader-order}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bursam retrieve trader order certificates';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $traderOrderId = $this->argument('trader-order');

        $traderOrder = TraderOrder::query()->find($traderOrderId);
        if (! $traderOrder) {
            $this->error("Trader order with ID: $traderOrderId not found!");
        } else {

            ProcessRetrieveOrderCertificates::dispatchSync($traderOrderId);

            $this->info('The command was successful!');
        }
    }
}
