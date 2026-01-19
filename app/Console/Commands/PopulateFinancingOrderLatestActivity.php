<?php

namespace App\Console\Commands;

use App\Contracts\Services\FinancingOrderActivityUpdateInterface;
use App\Models\FinancingOrder;
use Illuminate\Console\Command;

class PopulateFinancingOrderLatestActivity extends Command
{
    protected $signature = 'financing-orders:populate-latest-activity
                            {--chunk-size=1000 : Number of records to process at once}
                            {--force : Force update even if latest_activity is already set}';

    protected $description = 'Populate the latest_activity field for all existing financing orders';

    public function handle(): int
    {
        $chunkSize = $this->option('chunk-size') ?? 1000;
        $force = $this->option('force');

        $query = FinancingOrder::query();

        if (! $force) {
            $query->whereNull('latest_activity');
        }

        $totalRecords = $query->count();

        if (empty($totalRecords)) {
            $this->info('No financing orders need to be updated.');

            return 0;
        }

        $this->info("Updating {$totalRecords} financing orders...");

        $bar = $this->output->createProgressBar($totalRecords);
        $bar->start();

        $processed = 0;
        $service = app(FinancingOrderActivityUpdateInterface::class);

        // Process in chunks to avoid memory issues
        $query->chunk($chunkSize, function ($financingOrders) use ($bar, &$processed, $service) {
            foreach ($financingOrders as $order) {
                $service->updateFinancingOrderLatestActivity($order);
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
