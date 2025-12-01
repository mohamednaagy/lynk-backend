<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommoditySupplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SupplierMonthlyUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(['supplier_monthly_usage']),
            ],
        ];
    }
}
