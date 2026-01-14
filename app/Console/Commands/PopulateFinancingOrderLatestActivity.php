<?php

namespace App\Console\Commands;

use App\Models\FinancingOrder;
use Illuminate\Console\Command;

class PopulateFinancingOrderLatestActivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financing-orders:populate-latest-activity
                            {--chunk-size=1000 : Number of records to process at once}
                            {--force : Force update even if latest_activity is already set}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the latest_activity field for all existing financing orders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $chunkSize = $this->option('chunk-size');
        $force = $this->option('force');

        $query = FinancingOrder::query();

        if (! $force) {
            $query->whereNull('latest_activity');
        }

        $totalRecords = $query->count();

        if ($totalRecords === 0) {
            $this->info('No financing orders need to be updated.');

            return 0;
        }

        $this->info("Updating {$totalRecords} financing orders...");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;

        // Process in chunks to avoid memory issues
        $query->chunk($chunkSize, function ($financingOrders) use ($bar, &$processed) {
            foreach ($financingOrders as $order) {
                // Calculate the latest activity based on the same logic as the observer
                $latestActivity = $order->status->isNot(\App\Enums\FinancingOrderStatus::InProgress)
                      || is_null($order->current_step)
                      ? $order->status->description
                      : $order->current_step->description;

                // Update the record directly
                $order->update(['latest_activity' => $latestActivity]);
                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$processed} financing orders.");

        return 0;
    }
}
