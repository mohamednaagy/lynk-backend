<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface CalculateAmountWithVat
{
    /**
     * Create new user.
     */
    public function handle(Company $company, int $chargeAmountWithVat): array;
}
