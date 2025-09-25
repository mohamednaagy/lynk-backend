<?php

namespace App\Actions\Contracts\Companies;

use App\Enums\CompanyType;
use App\Models\Lender;

interface CreateCompany
{
    public function handle(array $data , int $companyType = CompanyType::Lender): Lender;
}
