<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface ProceedMurabhaCompleteDocument
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
