<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommoditySupplier;

use Illuminate\Foundation\Http\FormRequest;

class CommoditySuppliersLiteListRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }
}
