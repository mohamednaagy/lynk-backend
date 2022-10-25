<?php

namespace App\Actions\Contracts\Lenders;

use App\Models\Company;

interface GetLenderBalance
{
    /**
     * Create new user.
     *
     * @param  \App\Models\Company  $company
     * @return array
     */
    public function handle(Company $company): array;
}
