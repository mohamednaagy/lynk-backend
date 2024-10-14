<?php

namespace App\Http\Requests\V1\Admin\Lenders\Orders\TraderOrders;

use Illuminate\Foundation\Http\FormRequest;

class LocalMarketWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'case' => ['required', 'in:CommoditiesPurchased,FailedPurchase'],
            'external_order_no' => ['required', 'string'],
            'products' => ['required', 'array'],
            'products.*.uom' => ['required', 'string'],
            'products.*.product' => ['required', 'string'],
            'products.*.currency' => ['required', 'string'],
            'products.*.location' => ['required', 'string'],
            'products.*.quantity' => ['required', 'numeric'],
            'products.*.previous_owner' => ['required', 'string'],
            'products.*.original_supplier' => ['required', 'string'],
        ];
    }
}
