<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Actions\Contracts\Orders\DeductBalanceForNewOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessInProgressOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected mixed $financingOrder;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($financingOrder)
    {
        $this->financingOrder = $financingOrder;
    }

    /**
     * Execute the job.
     *
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        $driver = config('trader.default');
        $trader = Trader::driver($driver, get_latest_version_of_trader($driver));
        DB::multipleTransaction(function () use ($trader) {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrder);
            if ($financingOrder->traderOrders()->whereIn('status', [
                TraderOrderStatus::InProgress,
            ])->count() > 0) {
                return;
            }
            if ($financingOrder->status->cantMoveTo(FinancingOrderStatus::InProgress)) {
                return;
            }

            app(CanCreateOrder::class)->handle($financingOrder->company);

            $traderOrder = $trader->createTraderOrder($financingOrder);

            // keep below action after createTraderOrder()
            // to be sure we have a trader order and store his data in transaction meta
            app(DeductBalanceForNewOrder::class)->handle($traderOrder);

            $financingOrder->update([
                'status' => FinancingOrderStatus::InProgress,
            ]);
        });
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrder)];
    }
}
