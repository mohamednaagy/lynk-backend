<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommoditySupplier;

use App\Rules\CommodityUniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CommoditySuppliersListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'active' => ['nullable', 'integer', Rule::in([1, 2, 3])],
            'name' => ['nullable', 'string', 'max:100'],
            'unique_name' => ['nullable', 'string', 'max:100', new CommodityUniqueNameRule],
        ];
    }
}
