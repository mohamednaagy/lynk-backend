<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchasingCommodityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
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
            'product' => ['string', 'nullable'],
            'amount' => ['string', 'nullable'],
            'currency' => ['string', 'nullable'],
            'warehouse' => ['string', 'nullable'],
            'owner' => ['string', 'nullable'],
            'previous_owner' => ['string', 'nullable'],
            'new_owner' => ['string', 'nullable'],
            'date_time_of_purchasing_commodity' => ['string', 'nullable'],
            'warehouse_or_vault_emirates' => ['string', 'nullable'],
            'warehouse_or_vault_country' => ['string', 'nullable'],
            'inventory_record_id' => ['integer', 'nullable'],
            'warrant_percentage' => ['string', 'nullable'],
            'warehouse_or_vault_operator_id' => ['string', 'nullable'],
            'warrant_no' => ['string', 'nullable'],
            'hs_code' => ['string', 'nullable'],
            'uom' => ['string', 'nullable'],
            'exchange_rate' => ['string', 'nullable'],
            'auto_generate_financing_institution_certificate' => ['boolean', 'nullable'],
        ];
    }
}
