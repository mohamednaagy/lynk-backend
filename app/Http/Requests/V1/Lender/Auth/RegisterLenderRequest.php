<?php

namespace App\Http\Requests\V1\Lender\Auth;

use App\Models\Company;
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
            'email' => ['required', 'email'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            // __REVIEW__: add phone number type as "mobile" to the end of phone rule
            // See: https://github.com/Propaganistas/Laravel-Phone#validation
            'phone_number' => ['required', 'phone:phone_country_code', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'company_name' => ['required', 'string', 'min:3'],
            // __REVIEW__
            // 1. change regex to (^[a-zA-Z]+[a-zA-Z0-9_]*$)
            // 2. break down long rules on multiple lines. Example:
            // 'company_unique_name' => [
            //     'required',
            //     'string',
            //     Rule::unique(Company::class, 'unique_name'),
            //     'min:3',
            //     'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u'
            // ],
            // 3. add custom message for regex. see the following for reference: https://laravel.com/docs/9.x/validation#custom-messages-for-specific-attributes
            // Arabic: يجب أن يحتوي المعرف على أحرف إنجليزية وأرقام و _ فقط. بالإضافة يجب أن يبدأ بحرف إنجليزي
            // English: Identifier should contain only English letters, numbers and _. It should start with English letter
            'company_unique_name' => ['required', 'string', Rule::unique(Company::class, 'unique_name'), 'min:3', 'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u'],
            // __REVIEW__ remove min:1 and replace with size:10
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
        // __REVIEW__ no need to add this here as Laravel will add it automatically if it is in the validation file
        return [
            'phone_number.phone' => trans('validation.phone'),
        ];
    }
}
