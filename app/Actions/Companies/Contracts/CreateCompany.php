<?php

namespace App\Actions\Companies\Contracts;

use App\Models\Company;

interface CreateCompany
{
    /**
     * @param  array  $data
     * @return Company
     */
    public function handle(array $data): Company;
}
