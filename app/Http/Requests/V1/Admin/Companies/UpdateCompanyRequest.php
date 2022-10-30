<?php

namespace App\Http\Requests\V1\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
                'regex:/(^[a-zA-Z]+[a-zA-Z0-9\\-\\_]*$)/u',
                Rule::unique('companies', 'unique_name')
                    ->ignore($this->route('company')),
            ],
            'company_cr' => [
                'string',
                'min:1',
                Rule::unique('companies', 'company_cr')
                    ->ignore($this->route('company')),
            ],
            'visible' => ['required', 'boolean'],
            'internal' => ['required', 'boolean'],
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
