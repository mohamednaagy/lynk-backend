<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Rules\ValidateSAID;
use Illuminate\Foundation\Http\FormRequest;

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
        // __REVIEW__ remove pls
        $tenant = tenant();

        return [
            'reference_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'digits:10', new ValidateSAID],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            // __REVIEW__ we need to phone type "mobile" to phone validation rule
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            // __REVIEW__ amount should be greater than zero
            'amount' => ['required', 'numeric'],
            // __REVIEW__ selling_price should be greater than amount
            'selling_price' => ['required', 'numeric'],
            // __REVIEW__ remove pls
            'contract' => ['sometimes', 'file'],
            // __REVIEW__ remove pls
            'power_of_attorney' => ['sometimes', 'file'],
        ];
    }
}
