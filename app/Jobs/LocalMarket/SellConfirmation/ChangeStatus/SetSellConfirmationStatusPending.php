<?php

namespace App\Jobs\LocalMarket\SellConfirmation\ChangeStatus;

use App\Jobs\LocalMarket\SellConfirmation\BaseSellConfirmation;
use App\Jobs\LocalMarket\SellConfirmation\Enums\SellConfirmationStatus;
use App\Models\LocalMarketOrder;
use Illuminate\Database\Eloquent\Model;

class SetSellConfirmationStatusPending extends BaseSellConfirmation
{
    public function __construct(private int $localMarketOrderId)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->logInfo('Setting sell_confirmation_status to pending', [
            'order_id' => $this->localMarketOrderId,
        ]);

        Model::withoutEvents(function () {
            LocalMarketOrder::whereId($this->localMarketOrderId)->update([
                'sell_confirmation_status' => SellConfirmationStatus::Pending,
            ]);
        });
    }
}
