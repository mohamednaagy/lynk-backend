<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteTransactionsWithoutTraderOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:del-transactions-without-trader-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get transactions that don\'t have trader orders';

    protected $transactionsIds;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->loadTransactionsFromStorage();

        DB::connection(config('wallet.database.connection'))
            ->transaction(function () {
                foreach ($this->transactionsIds as $transactionId) {
                    $transaction = Transaction::find($transactionId);

                    if ($transaction === null) {
                        continue;
                    }

                    if ($transaction->meta['is_vat_included']) {
                        $transaction->delete();

                        continue;
                    }

                    $vatTransaction = Transaction::where('reference_number', $transaction->reference_number)
                        ->where('reason', TransactionReason::VatPercentageFee)
                        ->first();

                    if ($vatTransaction === null) {
                        $this->error(sprintf('No VAT transaction for transaction #%s', $transaction->id));

                        continue;
                    }

                    $transaction->delete();
                    $vatTransaction->delete();
                }
            });

        return Command::SUCCESS;
    }

    protected function loadTransactionsFromStorage()
    {
        $rawContent = Storage::get($this->option('filename'));

        if ($rawContent === null) {
            $this->error('File doesn\'t exist');

            return Command::FAILURE;
        }

        $this->transactionsIds = json_decode($rawContent, true)['transactions'];
    }
}
