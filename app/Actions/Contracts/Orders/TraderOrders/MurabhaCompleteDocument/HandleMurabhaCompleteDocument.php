<?php

namespace App\Actions\Contracts\Orders\TraderOrders\MurabhaCompleteDocument;

use App\Models\FinancingOrder;
use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandleMurabhaCompleteDocument
{
    public function handle(Request $request, FinancingOrder $order, TraderOrder $traderOrder): void;
}
