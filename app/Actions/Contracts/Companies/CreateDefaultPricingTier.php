<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface CreateDefaultPricingTier
{
    public function handle(Lender $lender);
}
