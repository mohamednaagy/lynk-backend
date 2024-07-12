<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityType;

use App\Enums\CommodityTypeStatus;
use App\Models\CommodityType;
use App\Rules\CommodityTypeUniqueNameRole;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommodityTypeRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:3',
                'max:64',
                Rule::unique(CommodityType::class, 'name'),
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
                new CommodityTypeUniqueNameRole(),
                Rule::unique(CommodityType::class, 'unique_name'),

            ],
            'status' => ['required',  new EnumValue(CommodityTypeStatus::class)],

        ];
    }

    public function messages()
    {
        return [
            'name.unique' => __('validation.unique_input'),
            'unique_name.unique' => __('validation.unique_input'),
        ];
    }
}
