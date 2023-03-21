<?php

namespace App\Http\Requests\V1\Admin\Settings;

use App\Enums\CompanyStatus;
use App\Enums\NotifyAboutNewOrderStatus;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @property string $area
 */
class UpdateLenderSettingsRequest extends FormRequest
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
            'default_order_cost' => ['required', 'numeric'],
            'email_verification_enabled' => ['required', 'boolean'],
            'notify_about_new_orders' => ['required', 'integer', new EnumValue(NotifyAboutNewOrderStatus::class)],
            'default_does_order_require_approval' => ['required', 'boolean'],
            'default_company_registration_status' => ['required', 'integer', new EnumValue(CompanyStatus::class)],
            'default_company_status_created_by_operation' => ['required', 'integer', new EnumValue(CompanyStatus::class)],
        ];
    }
}
