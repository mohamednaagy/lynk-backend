<?php

namespace App\Actions\LocalMarket;

use App\Actions\Contracts\LocalMarket\CreateLocalMarketOrder;
use App\Enums\LocalMarketOrderHistoryStatus;
use App\Enums\LocalMarketOrderStatus;
use App\Models\LocalMarketOrder;
use App\Support\Traders\Traits\LocalMarketHelperTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateLocalMarketOrderAction implements CreateLocalMarketOrder
{
    use LocalMarketHelperTrait;

    /**
     * @return LocalMarketOrder|Model
     */
    public function handle(array $data): LocalMarketOrder
    {
        $data['status'] = LocalMarketOrderStatus::initiate;
        $order = LocalMarketOrder::create(
            Arr::only($data, [
                'company_id',
                'customer_name',
                'external_order_no',
                'national_id',
                'amount',
                'currency',
                'status',
                'source',
                'comment',
                'preferred_commodity_type',
                'buying_uuid',
                'selling_uuid',
            ])
        );
        $this->createLocalMarketOrderHistory($order, LocalMarketOrderHistoryStatus::initiate);

        return $order;
    }
}
