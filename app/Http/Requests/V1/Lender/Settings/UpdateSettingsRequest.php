<?php

namespace App\Http\Requests\V1\Lender\Settings;

use App\Enums\CompanyStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property string $area
 */
class UpdateSettingsRequest extends FormRequest
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
     * @return array
     */
    public function rules(): array
    {
        return [
            'default_company_registration_status' => ['required', Rule::in(CompanyStatus::getValues())],
            'default_company_status_created_by_operation' => ['required', Rule::in(CompanyStatus::getValues())],
            'default_does_order_require_approval' => ['required', 'boolean'],
            'email_verification_enabled' => ['required', 'boolean'],
            'default_order_cost' => ['required', 'numeric'],
        ];
    }
}
