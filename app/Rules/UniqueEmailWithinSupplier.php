<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;

class UniqueEmailWithinSupplier implements Rule
{
    private $supplierId;

    public function __construct($supplierId)
    {
        $this->supplierId = $supplierId;
    }

    public function passes($attribute, $value)
    {
        return User::where('email', $value)
            ->where('company_id', $this->supplierId)
            ->doesntExist();
    }

    public function message()
    {
        return 'The email has already been taken.';
    }
}
