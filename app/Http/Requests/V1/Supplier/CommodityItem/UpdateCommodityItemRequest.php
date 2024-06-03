<?php

namespace App\Http\Requests\V1\Supplier\CommodityItem;

use App\Models\CommodityItem;
use App\Rules\CommodityItemUniqueNameRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommodityItemRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'unique_name' => [
                'required',
                'string',
                'min:3',
                'max:32',
                new CommodityItemUniqueNameRole(),
                Rule::unique(CommodityItem::class, 'unique_name')->where('company_id', tenant()->id)->ignore($this->route('commodity_item')),
            ],
            'name' => ['required', 'string',  'max:256'],
            'description' => ['nullable', 'string', 'max:512'],
            'commodity_type_id' => ['required', 'exists:commodity_types,id'],
            'max_price' => ['required', 'numeric', 'gt:0', 'gte:min_price'],
            'min_price' => ['required', 'numeric', 'gt:0', 'lte:max_price'],
            'currency_id' => ['required', 'exists:currencies,id'],
            'volume_sellable_unit' => ['required', 'numeric', 'regex:/^\d+(\.\d{1,5})?$/'],
            'measurement_id' => ['required', 'exists:measurements,id'],
        ];
    }

    public function messages()
    {
        return [
            'unique_name' => __('validation.unique_input'),
        ];
    }
}
