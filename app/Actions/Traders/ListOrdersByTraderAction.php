<?php

namespace App\Actions\Traders;

use App\Actions\Contracts\Traders\ListOrdersByTrader;
use App\Models\FinancingOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ListOrdersByTraderAction implements ListOrdersByTrader
{
    public function handle(string $trader): LengthAwarePaginator
    {
        return FinancingOrder::query()->withWhereHas('activeTraderOrder', function ($query) use ($trader) {
            $query->where('provider', Str::lower($trader));
        })->paginate();
    }
}
