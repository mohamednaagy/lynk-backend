<?php

namespace App\Http\Requests\V1\LocalMarket;

use App\Models\CommodityType;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BuyLocalMarketRequest extends FormRequest
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
            'company_id' => ['required', Rule::exists(Company::class, 'id')],
            'preferred_types.*' => ['nullable', 'array'],
            'preferred_types.id' => ['nullable', Rule::exists(CommodityType::class, 'id')],
            'loan_amount' => ['required', 'numeric'],
        ];
    }
}
