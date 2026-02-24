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
use Stancl\Tenancy\Tenancy;

class ProcessAutoCompleteSell implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(
        private readonly int $traderOrderId
    ) {
        // Disable tenancy inside the job
        app(Tenancy::class)->end();

        Log::channel(LOG_CHANNEL_AUTO_COMPLETE_SELL)->info('ProcessAutoCompleteSell job queued trader order id => '.$this->traderOrderId, [
            'traderOrderId' => $this->traderOrderId,
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
            Log::channel(LOG_CHANNEL_AUTO_COMPLETE_SELL)->info(formatLogTitle('Invalid trader order state', $traderOrder), [
                'financingOrderId' => $traderOrder?->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
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
            Log::channel(LOG_CHANNEL_AUTO_COMPLETE_SELL)->info(formatLogTitle('Invalid client state', $traderOrder), [
                'financingOrderId' => $financingOrder->id,
                'traderOrderId' => $this->traderOrderId,
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
            Log::channel(LOG_CHANNEL_AUTO_COMPLETE_SELL)->info(formatLogTitle('No valid period found', $traderOrder), [
                'financingOrderId' => $traderOrder->financing_order_id,
                'traderOrderId' => $this->traderOrderId,
                'created_at' => $traderOrder->created_at,
            ]);

            return null;
        }

        return $period;
    }

    private function handleFailure(\Throwable $e): void
    {
        Log::channel(LOG_CHANNEL_AUTO_COMPLETE_SELL)->error('ProcessAutoCompleteSell job failed trader_order_id => '.$this->traderOrderId, [
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
