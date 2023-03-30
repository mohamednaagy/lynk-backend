<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Rules\CompanyUniqueNameRule;
use BenSampo\Enum\Rules\EnumValue;
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
                    ->ignore($this->route('lender')),
            ],
            'company_cr' => [
                'string',
                'size:10',
                Rule::unique('companies', 'company_cr')
                    ->ignore($this->route('lender')),
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
            'notifications_email' => [
                'required',
                'email:filter',
                'string',
                'max:255',
            ],
            'notify_admins_about_new_orders' => [
                'required',
                'integer',
                new EnumValue(CompanyNewOrderNotificationForAdminStatus::class, false),
            ],

        ];
    }
}
