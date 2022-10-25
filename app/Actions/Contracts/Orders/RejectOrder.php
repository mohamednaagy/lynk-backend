<?php

namespace App\Actions\Contracts\Orders;

use App\Models\FinancingOrder;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

interface RejectOrder
{
    public function handle(FinancingOrder $financingOrder, User|Authenticatable $user, array $data): void;
}
