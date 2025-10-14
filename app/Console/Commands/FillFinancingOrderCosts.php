<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use App\Support\Collections\FinancingOrderCollection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FillFinancingOrderCosts extends Command
{
    protected $signature = 'fill:financing-order-costs {--chunk=1000}';

    protected $description = 'Calculate and fill financing order costs with VAT and without VAT';

    private int $successCount = 0;

    private int $failedCount = 0;

    public function handle()
    {
        $this->info('Starting cost calculation for financing orders...');
        $startTime = microtime(true);

        $total = FinancingOrder::where(function ($query) {
            $query->where('cost_with_vat', 0)
                ->orWhere('cost_without_vat', 0)
                ->orWhereNull('cost_with_vat')
                ->orWhereNull('cost_without_vat');
        })->count();
        $this->info("Total orders: {$total}");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        try {
            FinancingOrder::query()
                ->where(function ($query) {
                    $query->where('cost_with_vat', 0)
                        ->orWhere('cost_without_vat', 0)
                        ->orWhereNull('cost_with_vat')
                        ->orWhereNull('cost_without_vat');
                })
                ->select(['id'])
                ->orderBy('id')
                ->chunkById($this->option('chunk'), function ($orders) use ($bar) {
                    (new FinancingOrderCollection($orders))->loadCost();

                    foreach ($orders as $order) {
                        try {
                            if ($order->cost_with_vat?->getAmount() > 0 || $order->cost_without_vat?->getAmount() > 0) {
                                $order->update([
                                    'cost_with_vat' => $order->cost_with_vat?->getAmount() ?? 0,
                                    'cost_without_vat' => $order->cost_without_vat?->getAmount() ?? 0,
                                ]);
                                $this->successCount++;
                            }
                        } catch (\Exception $e) {
                            $this->failedCount++;
                            Log::error('Failed to update financing order', [
                                'order_id' => $order->id,
                                'message' => $e->getMessage(),
                            ]);
                        }
                        $bar->advance();
                    }

                    unset($orders);
                    gc_collect_cycles();
                });
        } catch (\Exception $e) {
            Log::error('FillFinancingOrderCosts failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error($e->getMessage());
        }

        $bar->finish();
        $this->newLine(2);

        $duration = round(microtime(true) - $startTime, 2);
        $this->info("✅ Completed in {$duration} seconds.");
        $this->info("Successful: {$this->successCount}");
        $this->info("Failed: {$this->failedCount}");
    }
}
