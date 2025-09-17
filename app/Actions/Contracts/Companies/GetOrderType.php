<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface GetOrderType
{
    public function handle(Lender $lender);
}
