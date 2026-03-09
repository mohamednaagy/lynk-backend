<?php

namespace App\Console\Commands;

use App\Jobs\Lenders\NotifyAboutRemainingBalanceLimit;
use App\Models\CompanyLenderDetail;
use App\Models\Lender;
use Illuminate\Console\Command;

class SendWalletRemainingBalanceNotificationCommand extends Command
{
    protected $signature = 'notifications:send-wallet-remaining-balance';

    protected $description = 'Send daily wallet remaining balance notification to Admin and LenderAdmin users per company';

    public function handle(): int
    {
        Lender::flushCache();
        CompanyLenderDetail::flushCache();

        Lender::with('lenderDetail')->whereHas('lenderDetail', function ($q) {
            $q->whereNotNull('min_wallet_limit');
        })->chunk(20, function ($lenders) {
            $lenders->each(function ($lender) {
                NotifyAboutRemainingBalanceLimit::dispatch($lender);
            });
        });

        $this->info('Command executed successfully!');

        return self::SUCCESS;
    }
}
