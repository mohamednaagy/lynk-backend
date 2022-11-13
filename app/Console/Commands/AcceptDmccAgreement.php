<?php

namespace App\Console\Commands;

use App\Support\Traders\Facades\Trader;
use Illuminate\Console\Command;

class AcceptDmccAgreement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dmcc:accept-agreement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accept DMCC Agreement';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $driver = config('trader.default');
        $trader = Trader::driver($driver);
        if ($trader->acceptAgreement()) {
            $this->line('Agreement Successfully Accepted');

            return Command::SUCCESS;
        }

        $this->error('Unable to Accept the Agreement');

        return Command::FAILURE;
    }
}
