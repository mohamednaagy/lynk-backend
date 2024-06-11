<?php

namespace App\Http\Requests\V1\Supplier\Inventories;

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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'location_id' => [
                'required',
                Rule::exists(SupplierLocation::class, 'id')->where('company_id', Auth()->user()->company_id),
                Rule::unique(LocalMarketInventory::class, 'id')->where('commodity_item_id', $this->item->id),

            ],
            'total_units' => ['required', 'numeric', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'location_id.unique' => 'This value already exists.',
            'total_units.min' => 'This field requires a positive integer value greater than 0'
        ];
    }
}
