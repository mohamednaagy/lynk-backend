<?php

namespace App\Actions\Orders;

use App\Actions\Contracts\Orders\GetOrder;
use App\Models\FinancingOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GetOrderAction implements GetOrder
{
    public function handle(int $order): Model|Collection|Builder|array|null
    {
        return FinancingOrder::query()->with('activeTraderOrder.traderHistories')->findOrFail($order);
    }
}
