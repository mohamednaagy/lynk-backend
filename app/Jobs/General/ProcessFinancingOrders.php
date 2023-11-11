<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\TraderOrderMode;
use App\Enums\TraderOrderStatus;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\Traders\Facades\Trader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessFinancingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $providersWithVersions = [
        [
            'provider' => 'dmcc',
            'versions' => ['v1'],
        ],
        [
            'provider' => 'fake',
            'versions' => ['v1'],
        ],
        [
            'provider' => 'bursam',
            'versions' => ['v2'],
        ],
    ];

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        FinancingOrder::query()
            ->where('status', FinancingOrderStatus::Approved)
            ->withCount(['traderOrders' => function ($query) {
                $query->where($this->scopeToProvidersWithVersionsClosure())
                    ->whereIn('status', [
                        TraderOrderStatus::InProgress,
                    ]);
            }])
            ->whereRelation('company', 'trading_mode', TraderOrderMode::Automatic)
            ->having('trader_orders_count', 0)
            ->chunk(10, function (Collection $orderCollection) {
                $orderCollection->each(function (FinancingOrder $order) {
                    ProcessInProgressOrder::dispatch($order->id);
                });
            });

        TraderOrder::query()
            ->withLastHistoryAction()
            ->where($this->scopeToProvidersWithVersionsClosure())
            ->where('can_continue_progress', true)
            ->whereIn('status', [
                TraderOrderStatus::InProgress,
            ])->chunk(10, function ($traderOrderCollection) {
                $traderOrderCollection->each(function (TraderOrder $traderOrder) {
                    Trader::driver($traderOrder->provider, $traderOrder->version)
                        ->dispatchJobForTransitioningFlow($traderOrder);
                });
            });
    }

    protected function scopeToProvidersWithVersionsClosure(): \Closure
    {
        return function ($query) {
            $isFirstLoopComplete = false;

            foreach ($this->providersWithVersions as $providerWithVersions) {
                $whereClosure = $this->scopeToProviderAndVersionsClosure(
                    $providerWithVersions['provider'],
                    $providerWithVersions['versions']
                );

                if ($isFirstLoopComplete) {
                    $query->orWhere($whereClosure);
                } else {
                    $query->where($whereClosure);
                    $isFirstLoopComplete = true;
                }
            }

            return $query;
        };
    }

    protected function scopeToProviderAndVersionsClosure($provider, $verions): \Closure
    {
        return fn ($query) => $query->where('provider', $provider)
            ->whereIn('version', $verions);
    }
}
