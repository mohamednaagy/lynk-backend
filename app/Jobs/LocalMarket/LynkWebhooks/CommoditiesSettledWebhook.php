<?php

namespace App\Jobs\LocalMarket\LynkWebhooks;

use App\Actions\Contracts\Orders\LocalMarketWebhook;
use App\Models\LocalMarketOrder;

class CommoditiesSettledWebhook extends BaseWebhook
{
    public function handle(LocalMarketWebhook $localMarketWebhook): void
    {
        $order = LocalMarketOrder::findOrFail($this->localMarketOrderId);
        $localMarketWebhook->with(['case' => 'commodities_settled', 'external_order_no' => $order->external_order_no])->handle();
    }
}
