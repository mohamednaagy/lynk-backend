<?php

namespace App\Actions\Contracts\Orders\TraderOrders;

use App\Models\User;

interface InitiateTraderOrder
{
    public function handle(User $user, int $orderId);
}
