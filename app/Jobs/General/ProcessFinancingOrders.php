<?php

namespace App\Jobs\General;

use App\Enums\FinancingOrderStatus;
use App\Enums\MurabhaStep;
use App\Enums\TraderOrderStatus;
use App\Jobs\Dmcc\ProcessDmccMpoOrder;
use App\Jobs\Dmcc\ProcessDmccRespondedToPtpOrder;
use App\Jobs\Dmcc\ProcessDmccSellingCommodityToCustomerOrder;
use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use App\Support\FinancingOrders\StepAndHistories\StepHistoriesDictionary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ProcessFinancingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $whiteListedProviders = ['dmcc', 'fake'];

        FinancingOrder::query()
            ->where('status', FinancingOrderStatus::Approved)
            ->withCount(['traderOrders' => function ($query) use ($whiteListedProviders) {
                $query->whereIn('provider', $whiteListedProviders)
                    ->whereIn('status', [
                        TraderOrderStatus::InProgress,
                        TraderOrderStatus::Completed,
                    ]);
            }])
            ->having('trader_orders_count', 0)
            ->chunk(10, function (Collection $orderCollection) {
                $orderCollection->each(function (FinancingOrder $order) {
                    ProcessInProgressOrder::dispatch($order->id);
                });
            });

        TraderOrder::query()
            ->withLastHistoryAction()
            ->whereIn('provider', $whiteListedProviders)
            ->whereIn('status', [
                TraderOrderStatus::InProgress,
            ])->chunk(10, function ($traderOrderCollection) {
                $traderOrderCollection->each(function (TraderOrder $traderOrder) {
                    $stepNode = app(StepHistoriesDictionary::class)->getStepByHistory($traderOrder->last_history_action);

                    match ($stepNode->step) {
                        MurabhaStep::PurchasingCommodity => ProcessDmccRespondedToPtpOrder::dispatch($traderOrder->id),
                        MurabhaStep::ClientWakalaCompleted => ProcessDmccSellingCommodityToCustomerOrder::dispatch($traderOrder->id),
                        MurabhaStep::ContractSigned => ProcessAskClientForWakala::dispatch($traderOrder->id),
                        MurabhaStep::CommoditySoldToCustomer => ProcessDmccMpoOrder::dispatch($traderOrder->id),
                        default => null,
                    };
                });
            });
    }
}
