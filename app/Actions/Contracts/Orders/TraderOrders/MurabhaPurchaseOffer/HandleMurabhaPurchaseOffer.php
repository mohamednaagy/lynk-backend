<?php

namespace App\Actions\Contracts\Orders\TraderOrders\MurabhaPurchaseOffer;

use App\Models\TraderOrder;
use Illuminate\Http\Request;

interface HandleMurabhaPurchaseOffer
{
    public function handle(Request $request, int $order, TraderOrder $traderOrder): void;
}
