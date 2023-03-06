<?php

namespace App\Actions\Contracts\Orders\TraderOrders\SellingCommodityToCustomer;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandleSellingCommodityToCustomer
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
