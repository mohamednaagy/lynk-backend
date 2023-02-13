<?php

namespace App\Jobs\Dmcc;

use App\Enums\FinancingOrderHistory;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessDmccExpiredOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $ttiId;

    protected mixed $notification;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($notification)
    {
        $this->notification = $notification;
        $this->ttiId = $this->notification->notificationHeaderAndEntity->notificationEntityDetails->notificationEntity[0]->entityValue;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->where('reference', $this->ttiId)
                ->where('status', TraderOrderStatus::InProgress)
                ->whereIn('provider', ['dmcc', 'fake'])
                ->lockForUpdate()
                ->first();

            if (! $traderOrder) {
                return;
            }

            $trader = Trader::driver($traderOrder->provider);

            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($traderOrder->financing_order_id);

            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::Expired)) {
                return;
            }

            $trader->updateOrderStatus($financingOrder, FinancingOrderStatus::Expired);

            $trader->createTraderOrderHistory(
                $traderOrder,
                FinancingOrderHistory::Expired
            );

            $traderOrder->update([
                'status' => TraderOrderStatus::Expired,
            ]);
        });
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('dmccTtiId'.$this->ttiId)];
    }
}
