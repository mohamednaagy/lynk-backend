<?php

namespace App\Actions\Contracts\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandleMurabhaPurchaseOffer
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
