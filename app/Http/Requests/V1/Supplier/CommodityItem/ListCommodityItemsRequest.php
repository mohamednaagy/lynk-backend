<?php

namespace App\Http\Requests\V1\Supplier\CommodityItem;

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
            'unique_name'               => ['nullable', 'string', 'max:32'],
            'name'                      => ['nullable', 'string', 'max:256'],
            'commodity_type'           => ['nullable', 'array'],
            'commodity_type.*.id'       => ['required', 'numeric'],
            'commodity_type.*.name'     => ['required', 'string'],
            'direction'                 => ['nullable', 'string', Rule::in('asc', 'desc')],
            'sort'                      => ['nullable' , 'string'],
            'page'                      => ['nullable', 'numeric'],
            'per_page'                  => ['nullable', 'numeric'],
        ];
    }
}
