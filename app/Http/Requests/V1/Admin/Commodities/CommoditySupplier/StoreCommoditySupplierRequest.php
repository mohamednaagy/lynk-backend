<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommoditySupplier;

use App\Enums\CommoitySupplierMarketType;
use App\Enums\CommoitySupplierStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Rules\CommodityUniqueNameRule;
use BenSampo\Enum\Rules\EnumValue;
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
                Rule::unique(Company::class, 'name')->where('type', CompanyType::Supplier),
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
                Rule::unique(Company::class, 'unique_name')->where('type', CompanyType::Supplier),

            ],
            'status' => ['required',  new EnumValue(CommoitySupplierStatus::class)],
            'market_type' => ['required',  new EnumValue(CommoitySupplierMarketType::class)],

        ];
    }

    public function messages()
    {
        return [
            'legal_name.unique' => __('validation.unique_input'),
            'unique_name.unique' => __('validation.unique_input'),
        ];
    }
}
