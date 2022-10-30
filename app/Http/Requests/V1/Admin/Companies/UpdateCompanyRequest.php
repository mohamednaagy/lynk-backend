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
            'name' => ['required'],
            'unique_name' => ['required', Rule::unique('companies', 'unique_name')->ignore($this->route('company'))],
            'company_cr' => ['required', Rule::unique('companies', 'company_cr')->ignore($this->route('company'))],
        ];
    }
}
