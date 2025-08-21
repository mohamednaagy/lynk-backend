<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Actions\Contracts\LocalMarket\PendingEligibleCommodities;
use App\Enums\LocalMarket\OrderHistoryStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CreateLocalMarketOrderAction implements CreateLocalMarketOrder
{
    use LocalMarketHelperTrait;

    /**
     * @return LocalMarketOrder|Model
     */
    public function handle(array $data): LocalMarketOrder
    {
        $order = LocalMarketOrder::create(
            Arr::only($data, [
                'company_id',
                'customer_name',
                'external_order_no',
                'national_id',
                'amount',
                'currency',
                'source',
                'comment',
                'preferred_commodity_type',
                'buying_uuid',
                'selling_uuid',
            ])
        );
        $this->createLocalMarketOrderHistory($order, OrderHistoryStatus::initiate);
        Log::channel(LOG_CHANNEL_LOCAL_MARKET)->info(formatLocalMarketOrderTitle( "saved new local market order at CreateLocalMarketOrderAction", $order) , [
            'localMarketOrderId' => $order->id,
        ]);
        app(PendingEligibleCommodities::class)->handle($order);

        return $order;
    }
}
