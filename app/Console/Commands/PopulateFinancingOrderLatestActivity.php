<?php

namespace App\Console\Commands;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
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

        // Process in chunks to avoid memory issues
        $query->chunk($chunkSize, function ($financingOrders) use ($bar, &$processed) {
            foreach ($financingOrders as $order) {
                $this->updateFinancingOrderLatestActivity($order);
                $processed++;
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$processed} financing orders.");

        return 0;
    }

    private function updateFinancingOrderLatestActivity(FinancingOrder $financingOrder): void
    {
        $financingOrder->latest_activity = $this->getLatestActivityDescription($financingOrder);
        Log::channel(LOG_CHANNEL_LYNK)
            ->debug('FinancingOrderActivityUpdateJob: updating latest_activity for order', [
                'order_id' => $financingOrder->id,
                'latest_activity' => $financingOrder->latest_activity,
            ]);
        $financingOrder->saveQuietly();
    }

    /**
     * Get the latest activity description based on status and current step
     */
    private function getLatestActivityDescription(FinancingOrder $financingOrder): string
    {
        return $this->withLocale('en', function () use ($financingOrder) {
            return $financingOrder->status->isNot(FinancingOrderStatus::InProgress)
            || is_null($financingOrder->current_step)
                ? $financingOrder->status->description
                : $financingOrder->current_step->description;
        });
    }
}
