<?php

namespace App\Http\Requests\V1\Admin\Commodities\CommodityInventory;

use App\Models\LocalMarketInventory;
use App\Models\SupplierLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocalMarketInventoryRequest extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'location_id' => [
                'required',
                Rule::exists(SupplierLocation::class, 'id')->where('company_id', $this->item->company_id)->withoutTrashed(),
                Rule::unique(LocalMarketInventory::class, 'supplier_location_id')->where('commodity_item_id', $this->item->id)->withoutTrashed(),
            ],
            'total_units' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'location_id.unique' => 'This value already exists.',
            'total_units.min' => 'This field requires a positive integer value greater than 0',
        ];
    }
}
