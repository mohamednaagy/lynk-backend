<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyStatusRequest extends FormRequest
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
            'status' => ['required', Rule::in(CompanyStatus::getValues())],
            'public_status_comment' => ['required', 'string', 'max:255'],
            'internal_status_comment' => ['required', 'string', 'max:255'],
        ];
    }
}
