<?php

namespace App\Jobs\General;

use App\Actions\Contracts\Orders\CanCreateOrder;
use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderStatus;
use App\Exceptions\BalanceIsNotEnoughException;
use App\Models\FinancingOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessInProgressOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $financingOrderId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $financingOrderId)
    {
        $this->financingOrderId = $financingOrderId;
    }

    /**
     * Execute the job.
     *
     *
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $financingOrder = FinancingOrder::query()->lockForUpdate()->findOrFail($this->financingOrderId);
            $trader = Trader::getSuitableDriverForCompany($financingOrder->company);
            DB::multipleTransaction(function () use ($trader, $financingOrder) {
                if ($financingOrder->traderOrders()->whereIn('status', [
                    TraderOrderStatus::InProgress,
                ])->count() > 0) {
                    Log::channel('lynk')->info('Financing order '.$financingOrder->id.' has in progress trader order');

                    return;
                }
                if (
                    $financingOrder->status->cantMoveTo(FinancingOrderStatus::InProgress)
                    || $financingOrder->company->lender->lenderDetail->require_initiate_trade_request
                ) {
                    Log::channel('lynk')->info('Financing order '.$financingOrder->id.' has in progress trader order');

                    return;
                }

                try {
                    app(CanCreateOrder::class)->handle($financingOrder->company, $financingOrder->amount);
                } catch (BalanceIsNotEnoughException $e) {
                    Log::channel('lynk')->info('Financing order '.$financingOrder->id.' has balance is not enough');
                    Log::alert($financingOrder->id);

                    return;
                }

                $trader->createTraderOrder($financingOrder);

                $financingOrder->update([
                    'status' => FinancingOrderStatus::InProgress,
                ]);
            });
        } catch (\Exception $e) {
            Log::channel('lynk')->error(
                'An error occurred while processing the financing order.',
                [
                    'financing_order_id' => $this->financingOrderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping('financingOrder'.$this->financingOrderId)];
    }
}
