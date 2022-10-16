<?php

namespace App\Http\Requests\V1\Lender\Auth;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterLenderRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'password' => ['required', 'confirmed', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'company_name' => ['required', 'string', 'min:3'],
            'company_unique_name' => ['required', 'string', Rule::unique(Company::class, 'unique_name'), 'min:3', 'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u'],
            'company_cr' => ['required', 'string', Rule::unique(Company::class, 'company_cr'), 'min:1'],
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
