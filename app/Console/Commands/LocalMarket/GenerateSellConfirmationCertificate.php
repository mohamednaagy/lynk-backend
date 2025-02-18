<?php

namespace App\Console\Commands\LocalMarket;

use App\Jobs\LocalMarket\SellConfirmation\CheckOrderUnitOwnershipSellConfirmation;
use Illuminate\Console\Command;

class GenerateSellConfirmationCertificate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'local-market:generate-sell-certificate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sell confirmation certificate for the old data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Dispatch a job to check the previous ownership of inventory units
        CheckOrderUnitOwnershipSellConfirmation::dispatch();

        return Command::SUCCESS;
    }
}
