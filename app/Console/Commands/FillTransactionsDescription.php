<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Support\Wallets\Contracts\TransactionUtilInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Traits\Localizable;

class FillTransactionsDescription extends Command
{
    use Localizable;

    protected $signature = 'app:fill-transactions-description
                            {--chunk-size=1000 : Number of records to process at once}
                            {--force : Force update even if description is already set}';

    protected $description = 'Populate the description field for all existing transactions';

    protected $locales = ['en', 'ar'];

    public function handle(): int
    {
        $chunkSize = (int) ($this->option('chunk-size') ?? 1000);
        $force = $this->option('force');

        $query = Transaction::query();

        if (! $force) {
            $query->whereNull('description');
        }

        $totalRecords = $query->count() * count($this->locales);

        if (empty($totalRecords)) {
            $this->info('No transactions need to be updated.');

            return self::SUCCESS;
        }

        $this->info("Updating {$totalRecords} transactions...");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $util = app(TransactionUtilInterface::class);
        foreach ($this->locales as $locale) {
            $this->withLocale($locale, function () use ($util, $chunkSize, &$processed, $bar) {
                Transaction::query()->chunk($chunkSize, function ($transactions) use ($util, &$processed, $bar) {
                    foreach ($transactions as $transaction) {
                        $transaction->description = ! is_null($transaction->reason)
                            ? $util->getDescription($transaction)
                            : null;
                        $transaction->saveQuietly();
                        $processed++;
                        $bar->advance();
                    }
                });
            });
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$processed} transactions.");

        return self::SUCCESS;
    }
}
