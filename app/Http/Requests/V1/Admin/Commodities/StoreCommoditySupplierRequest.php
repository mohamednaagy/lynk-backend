<?php

namespace App\Http\Requests\V1\Admin\Commodities;

use App\Models\CommoditySupplier;
use App\Rules\CommodityUniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommoditySupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'legal_name' => [
                'required',
                'string',
                'min:3',
                'max:64',
                Rule::unique(CommoditySupplier::class, 'legal_name'),
            ],

            'description' => [
                'nullable',
                'string',
                'min:3',
                'max:256',
            ],

            'unique_name' => [
                'required',
                'string',
                'min:3',
                new CommodityUniqueNameRule(),
                Rule::unique(CommoditySupplier::class, 'unique_name'),

            ],
            'status' => ['required'],
            'market_type' => ['required'],

        ];
    }
}
