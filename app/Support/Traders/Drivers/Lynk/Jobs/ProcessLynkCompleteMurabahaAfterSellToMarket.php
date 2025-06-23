<?php

namespace App\Support\Traders\Drivers\Lynk\Jobs;

use App\Enums\FinancingOrderHistory;
use App\Models\TraderOrder;
use App\Support\Traders\Traits\StopsTraderOrderOnJobFailure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLynkCompleteMurabahaAfterSellToMarket implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, StopsTraderOrderOnJobFailure;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected int $traderOrderId)
    {
        $this->onQueue('local_market_sell_commodity');
    }

    /**
     * Execute the job.
     *
     * @throws \Throwable
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $traderOrder = TraderOrder::query()
                ->lockForUpdate()
                ->find($this->traderOrderId);

            if (is_null($traderOrder)) {
                Log::warning('ProcessLynkCompleteMurabahaAfterSellToMarket: TraderOrder not found', [
                    'trader_order_id' => $this->traderOrderId,
                ]);
                throw new \Exception('TraderOrder not found with reference: '.$this->traderOrderId);
            }

            if (! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateSellingCommodityToCustomerDocument)) {
                Log::info('ProcessLynkCompleteMurabahaAfterSellToMarket: Order not in expected state', [
                    'trader_order_id' => $this->traderOrderId,
                    'last_action' => $traderOrder->traderHistories()->latest('id')->first()?->action,
                    'expected_action' => FinancingOrderHistory::CreateSellingCommodityToCustomerDocument,
                ]);
                throw new \Exception('Trader order is not in expected state with reference: '.$this->traderOrderId);
            }

            // Enhanced completion with better error handling
            try {
                (new \App\Support\Traders\TradingStrategies\TraderStrategyContext(
                    $traderOrder->provider,
                    $traderOrder->version
                ))->updateMurabhaCompleteDocument($traderOrder, []);

                Log::info('ProcessLynkCompleteMurabahaAfterSellToMarket: Successfully completed', [
                    'trader_order_id' => $this->traderOrderId,
                ]);
            } catch (\Exception $e) {
                Log::error('ProcessLynkCompleteMurabahaAfterSellToMarket: Failed to complete', [
                    'trader_order_id' => $this->traderOrderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        });
    }

    public function backoff()
    {
        return [120, 240, 300];
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    public function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }

    public function failed($exception)
    {
        Log::error('process sell commodity', ['traderOrderId ' => $this->traderOrderId, 'message' => $exception->getMessage()]);
    }
}
