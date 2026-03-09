<?php

namespace App\Jobs\FinancingOrders;

use App\Models\TraderOrder;
use App\Notifications\FinancingOrders\OrderDeliveryConfirmed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAboutOrderDeliveryConfirmed implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private TraderOrder $traderOrder)
    {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $notification = new OrderDeliveryConfirmed($this->traderOrder);

        $notification->sendTo([]);   // email BCC to eligible admins
        $notification->sendPortal(); // DB record + realtime push per admin with portal ON
    }
}
