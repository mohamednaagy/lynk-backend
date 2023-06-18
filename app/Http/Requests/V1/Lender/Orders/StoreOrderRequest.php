<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Rules\ValidateSAID;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;

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
            'customer_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'national_id' => ['required', 'string', 'size:10', new ValidateSAID()],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'string', 'phone:phone_country_code,mobile'],
            'amount' => ['required', 'numeric', 'gt:0'], // ?????????
            'selling_price' => ['required', 'numeric', 'gte:amount'],
            'is_verification_required' => ['required', 'boolean'],
        ];
    }
}
