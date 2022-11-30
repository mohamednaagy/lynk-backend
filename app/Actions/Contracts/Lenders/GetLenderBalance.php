<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface GetLenderBalance
{
    /**
     * Create new user.
     *
     * @param  Company  $company
     * @return array
     */
    public function handle(Company $company): array;
}
