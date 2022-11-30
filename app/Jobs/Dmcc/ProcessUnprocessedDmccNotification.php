<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessUnprocessedDmccNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $ttiId;

    protected string $notificationId;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->notificationId = $this->notification->notificationHeaderAndEntity->notificationId;
        $this->ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $driver = config('trader.default');

        if (! in_array($driver, ['dmcc', 'fake'])) {
            return;
        }

        $traderOrder = TraderOrder::query()->where('reference', $this->ttiId)->first();

        if (! $traderOrder) {
            return;
        }

        $financingOrder = $traderOrder->order;

        if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::Completed)) {
            return;
        }

        $trader = Trader::driver($driver);
        $trader->processNotification($this->notificationId);

        $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::Completed);

        $traderOrder->update([
            'status' => TraderOrderStatus::Completed,
        ]);
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('notificationId'.$this->notificationId)];
    }
}
