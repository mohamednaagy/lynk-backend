<?php

namespace App\Console\Commands;

use App\Enums\TraderOrderMode;
use App\Jobs\OverwriteOldLenderCertificateForBursaV2Orders;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\TraderHelperTrait;
use Illuminate\Console\Command;
use Illuminate\Support\Traits\Localizable;

class OverwriteOldLenderCertificateForBursaV2OrdersCommand extends Command
{
    use Localizable, TraderHelperTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bursam:regenerate-lender-certs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Overwrite old lender certs to have supplier as field';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        TraderOrder::query()
            ->select('id')
            ->orderBy('id')
            ->where('mode', TraderOrderMode::Automatic)
            ->where('version', 'v2')
            ->where('provider', 'bursam')
            ->chunk(100, function ($traderOrders) {
                $traderOrders->map(function ($traderOrder) {
                    OverwriteOldLenderCertificateForBursaV2Orders::dispatch($traderOrder->id);
                });
            });

        return Command::SUCCESS;
    }
}
