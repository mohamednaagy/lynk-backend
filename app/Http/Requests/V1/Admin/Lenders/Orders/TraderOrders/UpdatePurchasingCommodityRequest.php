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
            'ptp_document' => ['required', 'file', 'mimes:pdf'],
            'original_holding_certificate' => ['required', 'file', 'mimes:pdf'],
            'financing_institution_certificate' => [
                'exclude_if:auto_generate_financing_institution_certificate,true',
                'required',
                'file',
                'mimes:pdf',
            ],
            'product' => ['required', 'string'],
            'quantity' => ['required', 'numeric'],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string'],
            'warehouse' => ['required', 'string'],
            'owner' => ['required', 'string'],
            'previous_owner' => ['required', 'string'],
            'new_owner' => ['required', 'string'],
            'date_time_of_purchasing_commodity' => ['required', 'string', 'date_format:Y-m-d H:i'],
            'warehouse_or_vault_emirates' => ['required', 'string'],
            'warehouse_or_vault_country' => ['required', 'string'],
            'warehouse_or_vault_operator_id' => ['required', 'string'],
            'warrant_no' => ['required', 'string'],
            'hs_code' => ['required', 'string'],
            'uom' => ['required', 'string'],
            'exchange_rate' => ['required', 'string'],
            'auto_generate_financing_institution_certificate' => ['required', 'boolean'],
        ];
    }
}
