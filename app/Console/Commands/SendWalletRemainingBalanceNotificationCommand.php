<?php

namespace App\Console\Commands;

use App\Actions\Contracts\Lenders\GetLenderBalance;
use App\Jobs\Lenders\NotifyAboutRemainingBalanceLimit;
use App\Models\Lender;
use App\Support\QueryScoper\Scopes\Lenders\LendersWithMinWalletLimitScope;
use Illuminate\Console\Command;

class SendWalletRemainingBalanceNotificationCommand extends Command
{
    protected $signature = 'notifications:send-wallet-remaining-balance';

    protected $description = 'Send daily wallet remaining balance notification to Admin and LenderAdmin users per company';

    public function handle(): int
    {
        $query = Lender::with('lenderDetail');
        app(LendersWithMinWalletLimitScope::class)
            ->apply($query)
            ->chunk(20, function ($lenders) {
                $lenders
                    ->each(function (Lender $lender) {
                        $currentBalance = $this->getLenderCurrentBalance($lender);

                        if ($lender->lenderDetail->min_wallet_limit > $currentBalance) {
                            NotifyAboutRemainingBalanceLimit::dispatch($lender, $currentBalance);
                        }
                    });
            });

        $this->info('Command executed successfully!');

        return self::SUCCESS;
    }

    public function getLenderCurrentBalance(Lender $lender): float
    {
        $balances = app(GetLenderBalance::class)->handle($lender);
        /* @var \Cknow\Money\Money $balance */
        $balance = $balances['balance'];

        return (float) $balance->convertAndFormatByDecimal();
    }
}
