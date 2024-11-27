<?php

namespace App\Jobs\TraderOrder;

use App\Enums\FinancingOrderHistory;
use App\Enums\Trader;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\TraderOrder;
use App\Enums\TraderOrderCancelReason;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Support\Traders\Facades\Trader as FacadesTrader;

class ExpireOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The name of the queue on which the job should run.
     *
     * @var string
     */
    public $queue = 'expire_trader_order';

    /**
     * The trader order id.
     *
     * @var int
     */
    private int $traderOrderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $traderOrderId)
    {
        $this->traderOrderId = $traderOrderId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $traderOrder = TraderOrder::find($this->traderOrderId);
        if ($this->canExpireOrder($traderOrder)) {
            FacadesTrader::driver($traderOrder->provider, $traderOrder->version)
                ->cancelTraderOrder($traderOrder, TraderOrderCancelReason::ExpiredConfirmationTimeLimit);
        }
    }

    private function canExpireOrder(?TraderOrder $traderOrder): bool
    {
        return $traderOrder?->provider == Trader::Lynk
            && $traderOrder?->mode == TraderOrderMode::Automatic
            && $traderOrder?->status->is(TraderOrderStatus::InProgress)
            && $traderOrder?->checkOrderHistoryAction([FinancingOrderHistory::PendingDelivery])
            ?? false;
    }
}
