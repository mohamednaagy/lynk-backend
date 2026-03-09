<?php

declare(strict_types=1);

namespace App\Jobs\FinancingOrders;

use App\Actions\Contracts\FinancingOrderActivityRead;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Localizable;

class FinancingOrderActivityUpdateJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Localizable, Queueable, SerializesModels;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly FinancingOrder $financingOrder)
    {
        $this->onQueue('bursam');
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->financingOrder->id;
    }

    public function handle(): void
    {
        $this->financingOrder->latest_activity = app(FinancingOrderActivityRead::class)->getLatestActivityDescription($this->financingOrder);
        Log::channel(LOG_CHANNEL_LYNK)
            ->debug('FinancingOrderActivityUpdateJob: updating latest_activity for order', [
                'order_id' => $this->financingOrder->id,
                'latest_activity' => $this->financingOrder->latest_activity,
            ]);
        $this->financingOrder->saveQuietly();
    }
}
