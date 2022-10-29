<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface UpdateCompany
{
    /**
     * @param  Company  $company
     * @param  array  $data
     * @return Company
     */
    public function handle(Company $company, array $data): Company;
}
