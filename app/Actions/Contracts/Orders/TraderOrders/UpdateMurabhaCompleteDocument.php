<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface UpdateMurabhaCompleteDocument
{
    public function handle(Request $request, TraderOrder $traderOrder): void;
}
