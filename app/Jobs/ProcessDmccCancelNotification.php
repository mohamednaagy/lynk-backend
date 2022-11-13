<?php

namespace App\Jobs;

use App\Enums\FinancingOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccCancelNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $driver = config('trader.default');
        $trader = Trader::driver($driver);
        DB::transaction(function () use ($trader) {
            $ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
            $traderOrder = TraderOrder::query()->where('reference', $ttiId)->first();
            if (! $traderOrder) {
                return;
            }
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($traderOrder->financing_order_id);

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::Canceled);
        });
    }
}
