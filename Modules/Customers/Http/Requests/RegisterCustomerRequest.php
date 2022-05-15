<?php

namespace Modules\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */

    public function messages()
    {
        return [
            'phone_number.phone' => 'The :attribute field contains an invalid number.',
        ];
    }

    public function rules()
    {
        return [
            'first_name' => ['required', 'min:3'],
            'last_name' => ['required', 'min:3'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'min:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'numeric'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'min:8'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
