<?php

namespace App\Http\Requests\V1\Lender\Orders\Validators;

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
