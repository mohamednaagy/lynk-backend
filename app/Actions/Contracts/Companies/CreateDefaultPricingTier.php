<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface CreateDefaultPricingTier
{
    public function handle(Company $company);
}
