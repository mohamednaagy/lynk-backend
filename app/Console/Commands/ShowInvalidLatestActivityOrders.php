<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Contracts\FinancingOrderActivityRead;
use App\Models\FinancingOrder;
use Illuminate\Console\Command;

class ShowInvalidLatestActivityOrders extends Command
{
    protected $signature = 'financing-orders:show-invalid-latest-activity-orders';

    protected $description = 'List financing orders that have invalid latest activity.';

    public function handle(): int
    {
        $invalidLatestActivityOrdersCount = 0;
        FinancingOrder::latest()
            ->chunk(100, function ($financingOrders) use (&$invalidLatestActivityOrdersCount) {
                foreach ($financingOrders as $financingOrder) {
                    $originalLatestActivity = app(FinancingOrderActivityRead::class)->getLatestActivityDescription($financingOrder);
                    if ($financingOrder->latest_activity != $originalLatestActivity) {
                        $this->info("Financing order {$financingOrder->id} has latest activity `{$financingOrder->latest_activity}` but it should be `{$originalLatestActivity}`.");
                        $invalidLatestActivityOrdersCount++;
                    }
                }
            });

        $this->info("Found $invalidLatestActivityOrdersCount financing orders with invalid latest activity.");

        return self::SUCCESS;
    }
}
