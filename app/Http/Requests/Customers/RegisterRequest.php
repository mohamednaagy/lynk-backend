<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'min:3', 'string', 'max:100'],
            'last_name' => ['required', 'min:3', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'company_name' => ['required', 'string', 'min:3'],
            'company_unique_name' => ['required', 'string', 'unique:companies,unique_name', 'min:3', 'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u'],
            'company_cr' => ['required', 'string', 'min:1'],
            'source' => ['required', 'string'],
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'phone_number.phone' => trans('customers::validation.phone'),
        ];
    }
}
