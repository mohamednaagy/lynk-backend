<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Lender;

interface UpdateCompany
{
    /**
     * @param  Lender  $company
     * @param  array  $data
     * @return Lender
     */
    public function handle(Lender $lender, array $data): Lender;
}
