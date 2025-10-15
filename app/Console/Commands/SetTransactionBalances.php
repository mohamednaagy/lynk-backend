<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Cknow\Money\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetTransactionBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Example: php artisan transactions:set-balances
     */
    protected $signature = 'transactions:set-balances {--chunk=1000}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculate and set balance for all transactions by wallet.';

    public function handle(): int
    {
        $this->info('Starting to recalculate balances for all transactions...');

        // Get distinct wallet IDs
        $walletIds = Transaction::distinct()->pluck('wallet_id');
        $totalWallets = $walletIds->count();

        $this->info("Found {$totalWallets} wallets with transactions.");

        $this->output->progressStart($totalWallets);

        foreach ($walletIds as $walletId) {
            DB::beginTransaction();

            try {
                $previousBalance = null;

                Transaction::where('wallet_id', $walletId)
                    ->orderBy('id')
                    ->chunk($this->option('chunk'), function ($transactions) use (&$previousBalance) {
                        foreach ($transactions as $transaction) {
                            $amount = $transaction->amount instanceof Money
                                ? $transaction->amount
                                : Money::parse($transaction->amount, $transaction->currency);

                            if ($previousBalance === null) {
                                // first transaction → balance = amount
                                $newBalance = $amount;
                            } else {
                                // add or subtract using Money API
                                $newBalance = $previousBalance->add($amount);
                            }

                            $transaction->balance = $newBalance;
                            $transaction->saveQuietly();

                            $previousBalance = $newBalance;
                        }
                    });

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("❌ Failed for wallet_id {$walletId}: {$e->getMessage()}");
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();
        $this->info('✅ All transaction balances have been recalculated successfully.');

        return Command::SUCCESS;
    }
}
