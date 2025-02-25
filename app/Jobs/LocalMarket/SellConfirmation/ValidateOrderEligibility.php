<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Jobs\LocalMarket\SellConfirmation\Enums\UnitOwnershipStatus;
use App\Jobs\LocalMarket\SellConfirmation\Exceptions\ValidateOrderEligibilityException;
use App\Models\LocalMarketOrder;
use Exception;

class ValidateOrderEligibility extends BaseSellConfirmation
{
    public function __construct(private int $localMarketOrderId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        try {
            // Retrieve the order and check that all their units are either deleted or sold.
            $order = LocalMarketOrder::query()
                ->select('id')
                ->whereId($this->localMarketOrderId)
                ->where('sell_confirmation_status', SellConfirmationStatus::Pending)
                ->whereDoesntHave('orderUnits', function ($query) {
                    $query->where('ownership_status', UnitOwnershipStatus::Owner);
                })->first();

            if (! $order) {
                return;
            }

            $this->markOrderAsEligible($order->id);
            GenerateSellConfirmationCertificate::dispatch($order->id);
        } catch (Exception $e) {
            self::logError('ValidateOrderEligibility failed', [
                'order_id' => $this->localMarketOrderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new ValidateOrderEligibilityException($e->getMessage());
        }
    }

    private function markOrderAsEligible(int $orderId): void
    {
        $this->logInfo('Set sell_confirmation_status to "Ready For Certificate"', [
            'order_id' => $orderId,
        ]);

        LocalMarketOrder::whereId($orderId)->update([
            'sell_confirmation_status' => SellConfirmationStatus::ReadyForCertificate,
        ]);
    }
}
