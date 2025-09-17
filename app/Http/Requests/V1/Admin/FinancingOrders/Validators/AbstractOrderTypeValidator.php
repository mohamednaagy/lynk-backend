<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingOrderStatus;
use App\Models\Lender;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractOrderTypeValidator
{
    private Lender $lender;

    public function __construct(private int $lenderId)
    {
        $this->lender= Lender::find($this->lenderId);
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
        if ($this->lender->isForceUniqueReferenceNumber()) {
            return $this->lender->unique('financing_orders', 'reference_number')
                ->whereNot('status', FinancingOrderStatus::Cancelled);
        }
        return null;
    }


}


