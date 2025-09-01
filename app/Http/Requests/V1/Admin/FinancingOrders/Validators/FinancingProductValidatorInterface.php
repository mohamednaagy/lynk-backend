<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders\Validators;

interface FinancingProductValidatorInterface
{
    public function getRules(array $baseRules): array;
    public function getMessages(): array;
    public function getAttributes(): array;
}


