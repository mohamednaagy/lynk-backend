<?php

namespace App\Console\Commands;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Models\FinancingOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Traits\Localizable;

class PopulateFinancingOrderLatestActivity extends Command
{
    use Localizable;

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
        $action = app(FinancingOrderActivityUpdate::class);

        // Process in chunks to avoid memory issues
        $query->chunk($chunkSize, function ($financingOrders) use ($action, $bar, &$processed) {
            foreach ($financingOrders as $order) {
                $action->updateFinancingOrderLatestActivity($order);
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
