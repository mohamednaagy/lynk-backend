<?php

namespace App\Http\Requests\V1\Company;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3'],
            'unique_name' => ['required', 'string', 'min:3', 'unique:companies,unique_name'],
            'company_cr' => ['required', 'string', 'min:3', 'unique:companies,company_cr'],
            'order_cost' => ['required'],
        ];
    }
}
