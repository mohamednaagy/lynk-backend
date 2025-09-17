<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

use App\Enums\FinancingOrderStatus;
use App\Models\Lender;
use Illuminate\Validation\Rules\Unique;

abstract class AbstractFinancingOrderTypeValidator
{
    abstract public function getRules(): array;


    
    public function getMessages(): array
    {
        return [];
    }

    public function getAttributes(): array
    {
        return [];
    }


}


