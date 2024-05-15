<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Supplier;
use App\Models\User;

class UniqueEmailWithinSupplier implements Rule
{
    private $supplierId;

    public function __construct($supplierId)
    {
        $this->supplierId = $supplierId;
    }

    public function passes($attribute, $value)
    {
        $count = User::where('email', $value)
            ->whereHas('suppliers', function ($query) {
                $query->where('commodity_supplier_id', $this->supplierId);
            })->count();

        return $count === 0;
    }

    public function message()
    {
        return 'The email has already been taken.';
    }
}