<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface ProceedMurabhaCompleteDocument
{
    public function handle(Request $request, int $order, TraderOrder $traderOrder): void;
}
