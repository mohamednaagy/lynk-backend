<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
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
        // __REVIEW__ add translation for attributes
        // https://laravel.com/docs/9.x/validation#specifying-attribute-in-language-files
        return [
            'name' => [
                'required',
                'string',
                'min:3',
            ],
            'unique_name' => [
                'required',
                'string',
                'min:3',
                // __REVIEW__ use same regex as in app/Http/Requests/V1/Lender/Auth/RegisterLenderRequest.php
                // For better maintainability, use rule and use it here and in RegisterLenderRequest
                // __REVIEW__ we need to add translation for this rule (see https://laravel.com/docs/9.x/validation#custom-messages-for-specific-attributes)
                // Arabic: يجب أن يحتوي المعرف على أحرف إنجليزية وأرقام و _ فقط. بالإضافة يجب أن يبدأ بحرف إنجليزي
                // English: Identifier should contain only English letters, numbers and _. It should start with English letter
                'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u',
                Rule::unique(Company::class, 'unique_name'),

            ],
            'company_cr' => [
                'required',
                'string',
                // __REVIEW__ change min:1 to size:10
                'min:1',
                Rule::unique(Company::class, 'company_cr'),
            ],
            'does_order_require_approval' => [
                'required',
                'boolean',
            ],
            'order_cost' => [
                'required',
                'numeric',
            ],
        ];
    }
}
