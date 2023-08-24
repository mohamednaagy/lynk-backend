<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\OrderFeeType;
use App\Enums\TraderOrderMode;
use App\Models\Company;
use App\Rules\CompanyUniqueNameRule;
use App\Rules\OrderCostAmountTiersRangeRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        $tiersNumber = count($this->order_cost_tiers);

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
            'does_order_require_approval' => [
                'required',
                'boolean',
            ],
            'order_cost_tiers' => [
                'required',
                'array',
                new OrderCostAmountTiersRangeRule,
            ],
            'order_cost_tiers.*' => [
                'required',
                'array',
            ],
            'order_cost_tiers.0.order_value_start' => [
                'required',
                'numeric',
                Rule::in([0.00]),
            ],
            'order_cost_tiers.*.order_value_start' => [
                'required',
                'numeric',
                'decimal:2',
            ],
            'order_cost_tiers.*.order_value_end' => [
                'nullable',
                'numeric',
                'gt:order_cost_tiers.*.order_value_start',
                'decimal:2',
            ],
            'order_cost_tiers.'.($tiersNumber - 1).'.order_value_end' => [
                'prohibited',
            ],
            'order_cost_tiers.*.fee_type' => [
                'required',
                Rule::in(OrderFeeType::getValues()),
            ],
            'order_cost_tiers.*.order_cost_without_vat' => [
                'required',
                'numeric',
                'decimal:2',
            ],
            'order_cost_tiers.*.order_cost_with_vat' => [
                'required',
                'numeric',
                'gt:order_cost_tiers.*.order_cost_without_vat',
                'decimal:2',
            ],
            'order_cost_tiers.*.proration_amount' => [
                'required_if:order_cost_tiers.*.fee_type,'.OrderFeeType::Proration,
                'nullable',
                'numeric',
                'decimal:2',
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
            'trading_mode' => [
                'required',
                'string',
                new EnumValue(TraderOrderMode::class, false),
            ],
        ];
    }
}
