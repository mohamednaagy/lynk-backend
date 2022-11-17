<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Rules\ValidateSAID;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderWithoutVerificationRequest extends FormRequest
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
            'reference_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'string', Rule::phone()->country(['SA'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'selling_price' => ['required', 'numeric', 'gte:amount'],
        ];
    }
}
