<?php

namespace App\Jobs\TraderOrder\AutoCompleteSell;

use App\Actions\Contracts\Orders\TraderOrders\AutoCompleteSell;
use App\Enums\FinancingOrderHistory;
use App\Enums\TraderOrderStatus;
use App\Jobs\TraderOrder\AutoCompleteSell\Exceptions\AutoCompleteSellFailed;
use App\Models\ClientAutoSellPeriod;
use App\Models\CompanyLenderClient;
use App\Models\TraderOrder;
use App\Services\Company\CompanyLenderClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ProcessAutoCompleteSell implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    private const LOG_CHANNEL = 'bursam';

    public function __construct(
        private readonly int $traderOrderId
    ) {
        Log::channel(self::LOG_CHANNEL)->info('ProcessAutoCompleteSell job queued', [
            'trader_order_id' => $this->traderOrderId,
        ]);
    }

    public function handle(): void
    {
        try {
            $traderOrder = $this->getValidTraderOrder();
            if (! $traderOrder) {
                return;
            }

            $client = $this->getValidClient($traderOrder);
            if (! $client) {
                return;
            }

            $period = $this->getValidPeriod($client, $traderOrder);
            if (! $period) {
                return;
            }

            app(AutoCompleteSell::class)->handle($traderOrder, $period);
        } catch (\Throwable $e) {
            $this->handleFailure($e);
        }
    }

    public function middleware(): array
    {
        return [new WithoutOverlapping($this->uniqueId())];
    }

    private function evaluateAutoSellEligibility(): array
    {
        $traderOrder = $this->getValidTraderOrder();
        if (! $traderOrder) {
            return ['canAutoSell' => false];
        }

        $client = $this->getValidClient($traderOrder);
        if (! $client) {
            return ['canAutoSell' => false, 'traderOrder' => $traderOrder];
        }

        $period = $this->getValidPeriod($client, $traderOrder);
        if (! $period) {
            return ['canAutoSell' => false, 'traderOrder' => $traderOrder];
        }

        return [
            'canAutoSell' => true,
            'traderOrder' => $traderOrder,
            'period' => $period,
        ];
    }

    private function getValidTraderOrder(): ?TraderOrder
    {
        $traderOrder = TraderOrder::query()
            ->where('status', TraderOrderStatus::InProgress)
            ->find($this->traderOrderId);

        if (! $traderOrder || ! $traderOrder->doesLastActionMatchWith(FinancingOrderHistory::CreateTransferOwnershipToLenderDocument)) {
            Log::channel(self::LOG_CHANNEL)->info('Invalid trader order state', [
                'trader_order_id' => $this->traderOrderId,
                'exists' => ! is_null($traderOrder),
                'last_action' => $traderOrder?->last_history_action,
            ]);

            return null;
        }

        return $traderOrder;
    }

    private function getValidClient(TraderOrder $traderOrder): ?CompanyLenderClient
    {
        $financingOrder = $traderOrder->order;
        $client = CompanyLenderClient::with('autoSellPeriods')
            ->where([
                'national_id' => $financingOrder->national_id,
                'company_id' => $financingOrder->company_id,
            ])
            ->first();

        if (! $client || ! $client->auto_complete_sell) {
            Log::channel(self::LOG_CHANNEL)->info('Invalid client state', [
                'trader_order_id' => $this->traderOrderId,
                'client_exists' => ! is_null($client),
                'auto_complete_sell' => $client?->auto_complete_sell,
                'national_id' => $financingOrder->national_id,
                'company_id' => $financingOrder->company_id,
            ]);

            return null;
        }

        return $client;
    }

    private function getValidPeriod(CompanyLenderClient $client, TraderOrder $traderOrder): ?ClientAutoSellPeriod
    {
        $period = CompanyLenderClientService::getAutoCompleteSellPeriod($client, $traderOrder->created_at);

        if (! $period) {
            Log::channel(self::LOG_CHANNEL)->info('No valid period found', [
                'trader_order_id' => $this->traderOrderId,
                'created_at' => $traderOrder->created_at,
            ]);

            return null;
        }

        return $period;
    }

    private function handleFailure(\Throwable $e): void
    {
        Log::channel(self::LOG_CHANNEL)->error('ProcessAutoCompleteSell job failed', [
            'trader_order_id' => $this->traderOrderId,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        throw new AutoCompleteSellFailed($e->getMessage(), $e->getCode());
    }

    private function uniqueId(): string
    {
        return __CLASS__.'_'.$this->traderOrderId;
    }
}
