<?php

namespace App\Actions\Contracts\Companies;

use App\Models\Company;

interface CreateCompany
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company;
}
