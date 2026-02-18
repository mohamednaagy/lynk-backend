<?php

declare(strict_types=1);

namespace App\Jobs\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateJob implements ShouldBeUnique, ShouldQueue
{
    use Localizable;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly FinancingOrder $financingOrder)
    {
        $this->onQueue('default');
    }

    /**
     * The unique ID of the job.
     */
    public function uniqueId(): string
    {
        return (string) $this->financingOrder->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->financingOrder->latest_activity = $this->getLatestActivityDescription($this->financingOrder);
        Log::channel(LOG_CHANNEL_LYNK)
            ->debug('FinancingOrderActivityUpdateJob: updating latest_activity for order', [
                'order_id' => $this->financingOrder->id,
                'latest_activity' => $this->financingOrder->latest_activity,
            ]);
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
