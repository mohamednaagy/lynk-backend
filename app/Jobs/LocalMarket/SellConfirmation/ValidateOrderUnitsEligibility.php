<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
use App\Jobs\LocalMarket\SellConfirmation\Exceptions\ValidateOrderUnitsEligibilityException;
use App\Models\LocalMarketOrder;

class ValidateOrderUnitsEligibility extends BaseSellConfirmation
{
    public function handle(): void
    {
        try {
            // Step 1: Retrieve the IDs of all LocalMarketOrders with the sell_confirmation_status "Pending"
            // and ensure all related orderUnits have 'ownership_status' set to (2, 3)
            LocalMarketOrder::query()
                ->select('id')
                ->where('sell_confirmation_status', SellConfirmationStatus::Pending) // Pending status
                ->whereDoesntHave('orderUnits', function ($query) {
                    $query->where('ownership_status', UnitOwnershipStatus::Owner);
                })->chunkById(self::CHUNK_SIZE, function ($orders) {
                    $orderIds = $orders->pluck('id')->toArray();

                    // Step 2: Update the 'sell_confirmation_status' to "All units sold" (1)
                    LocalMarketOrder::whereIn('id', $orderIds)->update([
                        'sell_confirmation_status' => SellConfirmationStatus::ReadyForCertificate,
                    ]);

                    // Step 3: Dispatch the job for generating the Sell Confirmation Certificate
                    foreach ($orderIds as $orderId) {
                        GenerateSellConfirmationCertificate::dispatch($orderId);
                    }
                });
        } catch (\Exception $e) {
            self::logError('ValidateOrderUnitsEligibility failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ValidateOrderUnitsEligibilityException($e->getMessage());
        }
    }
}
