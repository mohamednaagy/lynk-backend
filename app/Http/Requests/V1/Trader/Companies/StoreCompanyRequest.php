<?php

namespace App\Http\Requests\V1\Trader\Companies;

use App\Models\Company;
use App\Rules\CompanyUniqueNameRule;
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
                new CompanyUniqueNameRule,
                Rule::unique(Company::class, 'unique_name'),
            ],
            'company_cr' => [
                'required',
                'string',
                'size:10',
                Rule::unique(Company::class, 'company_cr'),
            ],
            'driver' => [
                'nullable',
                'string',
                Rule::unique(Company::class, 'driver'),
                Rule::in(['dmcc', 'fake']),
            ],
        ];
    }
}
