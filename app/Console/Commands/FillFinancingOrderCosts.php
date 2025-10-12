<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use Illuminate\Console\Command;

class FillFinancingOrderCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fill:financing-order-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        FinancingOrder::orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $financingOrder) {
                    $transaction = $financingOrder->creationFeeTransactions()->latest()->first();

                    if (! $transaction) {
                        continue;
                    }

                    $meta = $transaction->meta;
                    $financingOrder->cost_with_vat = abs($transaction->amount->getAmount());

                    try {
                        if ($meta['is_vat_included']) {
                            $financingOrder->cost_without_vat = $meta['order_cost']['amount'];
                        } else {
                            $financingOrder->cost_without_vat = $meta['order_cost']['amount'] - ($meta['order_cost']['amount'] * 0.15);
                        }

                        $financingOrder->saveQuietly();
                    } catch (\Throwable $e) {
                        logger()->error('Failed to update FinancingOrder ID '.$financingOrder->id, [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}
