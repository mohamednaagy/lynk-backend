<?php

namespace App\Http\Requests\V1\Admin\Customers;

use Illuminate\Foundation\Http\FormRequest;
use function trans;

class StoreCustomerRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'min:8'],
        ];

        if (! empty($this->role)) {
            $rules['role'] = ['required', 'string', 'exists:roles,name'];
        }

        if (! empty($this->permissions)) {
            $rules['permissions.*'] = ['required', 'array', 'distinct'];
        }

        return $rules;
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
