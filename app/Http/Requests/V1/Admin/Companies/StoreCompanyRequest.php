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
                Rule::unique(Company::class, 'unique_name'),

            ],
            'company_cr' => [
                'required',
                'string',
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
