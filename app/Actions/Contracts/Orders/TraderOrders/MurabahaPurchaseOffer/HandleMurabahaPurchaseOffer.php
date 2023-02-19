<?php

namespace App\Actions\Contracts\Orders\TraderOrders\MurabahaPurchaseOffer;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandleMurabahaPurchaseOffer
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
