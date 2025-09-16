<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingOrderStatus;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractFinancialProductValidator
{
    private Company $company;
    private CompanyLenderDetail $lenderDetail;

    public function __construct(private int $companyId)
    {
        $this->company = Company::find($this->companyId);
        $this->lenderDetail = $this->company->lender->lenderDetail;
    }

    abstract public function getRules(): array;


    
    public function getMessages(): array
    {
        return [];
    }

    public function getAttributes(): array
    {
        return [];
    }


    protected function handleUniqueReferenceNumber(): ?Unique
    {
        if ($this->lenderDetail->force_unique_reference_number) {
            return $this->company->unique('financing_orders', 'reference_number')
                ->whereNot('status', FinancingOrderStatus::Cancelled);
        }
        return null;
    }


}


