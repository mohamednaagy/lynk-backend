<?php

namespace App\Jobs\LocalMarket\SellConfirmation;

use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Models\LocalMarketOrder;

class DispatchOrderUnitOwnershipChecks extends BaseSellConfirmation
{
    public function __construct(private ?int $inventoryId = null)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        LocalMarketOrder::query()->where('sell_confirmation_status', SellConfirmationStatus::Pending)
            ->chunkById(self::CHUNK_SIZE, function ($orders) {
                foreach ($orders as $order) {
                    CheckOrderUnitOwnership::dispatch($order->id, $this->inventoryId);
                }
            });
    }

    public function uniqueId(): string
    {
        return $this->inventoryId ?
            __CLASS__.'_'.$this->inventoryId
            : parent::uniqueId();
    }
}
