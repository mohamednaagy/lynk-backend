<?php

namespace App\Actions\Companies;

use App\Actions\Contracts\Companies\CreateCompany;
use App\Enums\CompanyType;
use App\Models\Lender;
use Illuminate\Support\Arr;

class CreateCompanyAction implements CreateCompany
{
    public function __construct(
        private CompanyCreationStrategyFactory $companyCreationStrategyFactory
    ) {}

    public function handle(array $data,int $companyType = CompanyType::Lender): Lender
    {
        $data['type'] = $companyType;
        $getSuitableStrategy = $this->companyCreationStrategyFactory->make($companyType);
        return $getSuitableStrategy->create($data);
    }

   
}
