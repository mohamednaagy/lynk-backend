<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

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
            'status' => ['required', new EnumValue(CompanyStatus::class)],
            'public_status_comment' => ['required', 'string', 'max:2000'],
            'internal_status_comment' => ['required', 'string', 'max:2000'],
        ];
    }
}
