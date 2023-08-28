<?php

namespace App\Console\Commands;

use App\Enums\TransactionReason;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDuplicateOrdersCreationFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:del-duplicate-creation-fees {--filename=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete duplication creation fees';

    protected $tradersOrderIds;

    protected $currentTraderOrderId;

    protected $traderOrderTransactions;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->loadTraderOrdersIdsFromStorage();

        foreach ($this->tradersOrderIds as $id) {
            $this->currentTraderOrderId = $id;

            $this->loadTransactions();

            $this->deleteFirstTransaction();

            DB::connection(config('wallet.database.connection'))
                ->transaction(function () {
                    foreach ($this->traderOrderTransactions as $transaction) {
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
        }

        return Command::SUCCESS;
    }

    protected function loadTraderOrdersIdsFromStorage()
    {
        $rawContent = Storage::get($this->option('filename'));

        if ($rawContent === null) {
            $this->error('File doesn\'t exist');

            return Command::FAILURE;
        }

        $this->tradersOrderIds = json_decode($rawContent, true)['trader_orders_ids'];
    }

    protected function loadTransactions()
    {
        $this->traderOrderTransactions = Transaction::where(
            'meta->trader_order_id',
            $this->currentTraderOrderId
        )
            ->where('reason', TransactionReason::OrderCreationFee)
            ->oldest('created_at')
            ->get();

        return $this;
    }

    protected function deleteFirstTransaction()
    {
        $this->traderOrderTransactions->shift();
    }
}
