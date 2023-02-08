<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Models\Company;
use App\Rules\CompanyUniqueNameRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
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
                new CompanyUniqueNameRule,
                Rule::unique('companies', 'unique_name')
                    ->ignore($this->route('company')),
            ],
            'company_cr' => [
                'string',
                'size:10',
                Rule::unique('companies', 'company_cr')
                    ->ignore($this->route('company')),
            ],
            'does_order_require_approval' => [
                'required',
                'boolean',
            ],
            'order_cost' => [
                'required',
                'numeric',
            ],
            'public_status_comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'internal_status_comment' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'email' => [
                'required',
                'email',
                'string',
                Rule::unique(Company::class, 'email')
                    ->ignore($this->route('company')),
                'max:255',
            ],
        ];
    }
}
