<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Contracts\FinancingOrderActivityUpdate;
use App\Models\FinancingOrder;
use Illuminate\Console\Command;

class ShowInvalidLatestActivityOrders extends Command
{
    protected $signature = 'financing-orders:show-invalid-latest-activity-orders';

    protected $description = 'List financing orders that have invalid latest activity.';

    public function handle(): int
    {
        $invalidLatestActivityOrdersCount = 0;
        $action = app(FinancingOrderActivityUpdate::class);

        FinancingOrder::latest()
            ->chunk(100, function ($financingOrders) use ($action, &$invalidLatestActivityOrdersCount) {
                foreach ($financingOrders as $financingOrder) {
                    $originalLatestActivity = $action->getLatestActivityDescription($financingOrder);
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
