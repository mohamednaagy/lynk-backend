<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Enums\LocalMarketOrderStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
use App\Jobs\LocalMarket\SellConfirmation\Exceptions\ValidateOrderEligibilityException;
use App\Models\LocalMarketOrder;
use Exception;
use Illuminate\Support\Collection;

class ValidateOrderEligibility extends BaseSellConfirmation
{
    public function handle(): void
    {
        try {
            // Retrieve pending orders and check that all their units are either deleted or sold.
            LocalMarketOrder::query()
                ->select('id')
                ->whereIn('status', [LocalMarketOrderStatus::CommoditiesSell, LocalMarketOrderStatus::Completed])
                ->where('sell_confirmation_status', SellConfirmationStatus::Pending)
                ->whereDoesntHave('orderUnits', function ($query) {
                    $query->where('ownership_status', UnitOwnershipStatus::Owner);
                })->chunkById(self::CHUNK_SIZE, function ($orders) {
                    $this->markOrdersAsEligible($orders);
                    $this->dispatchSellConfirmationJobs($orders);
                });
        } catch (Exception $e) {
            self::logError('ValidateOrderEligibility failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ValidateOrderEligibilityException($e->getMessage());
        }
    }

    private function markOrdersAsEligible(Collection $orders): void
    {
        $orderIds = $orders->pluck('id')->toArray();

        LocalMarketOrder::whereIn('id', $orderIds)->update([
            'sell_confirmation_status' => SellConfirmationStatus::ReadyForCertificate,
        ]);
    }

    private function dispatchSellConfirmationJobs(Collection $orders): void
    {
        foreach ($orders as $order) {
            GenerateSellConfirmationCertificate::dispatch($order->id);
        }
    }
}
