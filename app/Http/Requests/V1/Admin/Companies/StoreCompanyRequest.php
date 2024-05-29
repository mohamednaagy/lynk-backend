<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\CompanyType;
use App\Enums\OrderFeeType;
use App\Enums\TraderOrderMode;
use App\Models\Company;
use App\Rules\CheckActiveCommodityTypeRule;
use App\Rules\CompanyUniqueNameRule;
use App\Rules\OrderCostTiersRangeRule;
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
        $lastTierIndex = '*';
        if ($this->order_cost_tiers) {
            $lastTierIndex = count($this->order_cost_tiers) - 1;
        }

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
                Rule::unique(Company::class, 'unique_name')->where('type', CompanyType::Lender),

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
            'require_initiate_trade_request' => [
                'nullable',
                'required_if:trading_mode,'.TraderOrderMode::Automatic,
                'boolean',
            ],
            'order_cost_tiers' => [
                'required',
                'array',
                new OrderCostTiersRangeRule,
            ],
            'order_cost_tiers.*' => [
                'required',
                'array',
            ],
            'order_cost_tiers.*.order_value_start' => [
                'required',
                'decimal:0,2',
            ],
            'order_cost_tiers.0.order_value_start' => [
                'required',
                'numeric',
                Rule::in([0.00, 0, 0.0, '0', '0.0', '0.00']),
            ],
            'order_cost_tiers.*.order_value_end' => [
                'nullable',
                'gt:order_cost_tiers.*.order_value_start',
                'decimal:0,2',
            ],
            'order_cost_tiers.'.$lastTierIndex.'.order_value_end' => [
                'prohibited',
            ],
            'order_cost_tiers.*.fee_type' => [
                'required',
                Rule::in([OrderFeeType::Fixed]),
            ],
            'order_cost_tiers.'.$lastTierIndex.'.fee_type' => [
                'required',
                Rule::in(OrderFeeType::getValues()),
            ],
            'order_cost_tiers.*.order_cost_with_vat' => [
                'required',
                'decimal:0,2',
            ],
            'order_cost_tiers.'.$lastTierIndex.'.proration_amount' => [
                'exclude_unless:order_cost_tiers.'.$lastTierIndex.'.fee_type,'.OrderFeeType::Proration,
                'required_if:order_cost_tiers.'.$lastTierIndex.'.fee_type,'.OrderFeeType::Proration,
                'nullable',
                'decimal:0,2',
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
            'notify_borrowers_about_order_updates' => [
                'required',
                'boolean',
            ],
            'force_unique_reference_number' => [
                'required',
                'boolean',
            ],
            'trading_mode' => [
                'required',
                'string',
                new EnumValue(TraderOrderMode::class, false),
            ],

            'contract_number' => [
                'required',
                'string',
                Rule::unique(Company::class, 'contract_number'),
                'min:4',
                'max:16',
            ],

            'preferred_market_type' => [
                'required',
                'integer',
                new EnumValue(CompanyMarketType::class, false),
            ],

            'preferred_commodity_types ' => [
                'nullable', 'array',
            ],

            'preferred_commodity_types.*' => [
                'required', new CheckActiveCommodityTypeRule(),
            ],
        ];
    }
}
