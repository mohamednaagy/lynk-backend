<?php

namespace App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandlePurchasingCommodity
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
