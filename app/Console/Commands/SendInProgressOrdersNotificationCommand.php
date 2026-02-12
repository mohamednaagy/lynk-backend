<?php

namespace App\Console\Commands;

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Jobs\FinancingOrders\NotifyAboutInProgressOrders;
use App\Models\Lender;
use Illuminate\Console\Command;

class SendInProgressOrdersNotificationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:send-in-progress-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily in-progress orders notification to Admin and LenderAdmin users per company';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lenderIds = Lender::whereHas('orders', function ($q) {
            $q->where('status', FinancingOrderStatus::InProgress)
                ->whereHas('traderOrders', function ($q) {
                    $q->where('status', TraderOrderStatus::InProgress);
                });
        })->get(['id'])->pluck('id');

        if ($lenderIds->isEmpty()) {
            $this->info('No companies with in-progress trader orders.');

            return self::SUCCESS;
        }

        foreach ($lenderIds as $lenderId) {
            NotifyAboutInProgressOrders::dispatch($lenderId);
        }

        return self::SUCCESS;
    }
}
