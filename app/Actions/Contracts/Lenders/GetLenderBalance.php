<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface GetLenderBalance
{
    /**
     * Create new user.
     */
    public function handle(Company $company): array;
}
