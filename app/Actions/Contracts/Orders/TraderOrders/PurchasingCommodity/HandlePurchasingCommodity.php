<?php

namespace App\Actions\Contracts\Orders\TraderOrders\PurchasingCommodity;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandlePurchasingCommodity
{
    public function handle(Request $request, int $order, TraderOrder $traderOrder): void;
}
