<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Lender;

interface GetLenderBalance
{
    /**
     * Create new user.
     */
    public function handle(Lender $lender): array;
}
