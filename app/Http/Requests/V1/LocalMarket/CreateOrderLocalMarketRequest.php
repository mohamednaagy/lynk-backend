<?php

namespace App\Http\Requests\V1\LocalMarket;

use App\Models\CommodityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderLocalMarketRequest extends FormRequest
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
            'source' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'national_id' => ['required', 'numeric'],
            'customer_name' => ['required', 'string'],
            'comment' => ['nullable', 'string'],
            'company_id' => ['required', 'numeric'],
            'reference' => ['required', 'string'],
            'currency' => ['required', 'string'],
            'preferred_commodity_type' => ['nullable', 'array'],
            'preferred_commodity_type.*' => ['nullable', Rule::exists(CommodityType::class, 'id')],

        ];
    }
}
