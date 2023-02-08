<?php

namespace App\Http\Requests\V1\Lender\Auth;

use App\Models\Company;
use App\Rules\CompanyUniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'first_name' => ['required', 'string', 'min:3', 'max:100'],
            'last_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email', Rule::unique(Company::class, 'email')],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'min:3'],
            'company_unique_name' => [
                'required',
                'string',
                Rule::unique(Company::class, 'unique_name'),
                'min:3',
                new CompanyUniqueNameRule,
            ],
            'company_cr' => [
                'required',
                'string',
                Rule::unique(Company::class, 'company_cr'),
                'size:10',
            ],
            'source' => ['required', 'string'],
        ];
    }
}
