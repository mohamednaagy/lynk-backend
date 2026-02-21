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
     *
     * @return void
     */
    public function handle()
    {
        $notification = new OrderDeliveryConfirmed($this->traderOrder);
        // no need to pass emails, since the admins emails will be included internally as BCC
        $notification->sendTo([]);
    }
}
