<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use App\Support\Collections\FinancingOrderCollection;
use Illuminate\Console\Command;

class FillFinancingOrderCosts extends Command
{
    protected $signature = 'fill:financing-order-costs {--chunk=1000}';

    protected $description = 'Calculate and fill financing order costs with VAT and without VAT';

    public function handle()
    {
        $this->info('Starting cost calculation for financing orders...');
        $startTime = microtime(true);

        $total = FinancingOrder::count();
        $this->info("Total orders: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        FinancingOrder::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById($this->option('chunk'), function ($orders) use ($bar) {
                (new FinancingOrderCollection($orders))->loadCost();

                foreach ($orders as $order) {
                    $order->update([
                        'cost_with_vat' => $order->cost_with_vat?->getAmount() ?? 0,
                        'cost_without_vat' => $order->cost_without_vat?->getAmount() ?? 0,
                    ]);

                    $bar->advance();
                }

                unset($orders);
                gc_collect_cycles();
            });

        $bar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✅ Completed in {$duration} seconds.");
    }
}
