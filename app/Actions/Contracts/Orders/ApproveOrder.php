<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\User;

interface ApproveOrder
{
    public function handle(FinancingOrder $financingOrder, User $user);
}
