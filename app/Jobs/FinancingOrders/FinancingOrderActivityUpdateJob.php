<?php

declare(strict_types=1);

namespace App\Jobs\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateJob implements ShouldQueue
{
    use Localizable;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly FinancingOrder $financingOrder)
    {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->financingOrder->latest_activity = $this->getLatestActivityDescription($this->financingOrder);
        Log::channel(LOG_CHANNEL_LYNK)
            ->debug('FinancingOrderActivityUpdateJob update latest_activity to: '.$this->financingOrder->latest_activity.' for order: '.$this->financingOrder->id);
        $this->financingOrder->saveQuietly();
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
