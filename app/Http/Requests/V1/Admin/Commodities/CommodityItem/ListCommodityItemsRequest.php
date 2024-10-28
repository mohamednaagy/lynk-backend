<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityItem;

use App\Enums\CommodityItemStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ListCommodityItemsRequest extends FormRequest
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
            'unique_name'               => ['nullable', 'string'],
            'name'                      => ['nullable', 'string'],
            'supplier'                  => ['nullable', 'array'],
            'supplier.*.id'             => ['required', 'numeric'],
            'suppliers.*.name'          => ['required', 'string'],
            'commodity_types'           => ['nullable', 'array'],
            'commodity_type.*.id'       => ['required', 'numeric'],
            'commodity_type.*.name'     => ['required', 'string'],
            'direction'                 => ['nullable', 'string', Rule::in('asc', 'desc')],
            'sort'                      => ['nullable' , 'string'],
            'page'                      => ['nullable', 'numeric'],
            'per_page'                  => ['nullable', 'numeric'],
        ];
    }
}
