<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyMarketType;
use App\Enums\CompanyNewOrderNotificationForAdminStatus;
use App\Enums\CompanyType;
use App\Enums\FinancingOrderTypeEnum;
use App\Enums\OrderFeeType;
use App\Enums\TraderOrderMode;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
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
                Rule::unique(CompanyLenderDetail::class, 'company_cr'),
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
            'auto_complete_murabaha_order' => [
                'required',
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
            'allow_preferred_commodity_in_order' => [
                'nullable',
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
                Rule::unique(CompanyLenderDetail::class, 'contract_number'),
                'min:4',
                'max:16',
            ],

            'preferred_market_type' => [
                'nullable',
                'required_if:trading_mode,'.TraderOrderMode::Automatic,
                'integer',
                new EnumValue(CompanyMarketType::class, false),
            ],

            'preferred_commodity_types ' => [
                'nullable', 'array',
            ],

            'preferred_commodity_types.*' => [
                'required', new CheckActiveCommodityTypeRule,
            ],

            'default_contract_sign_time_limit' => [
                'nullable', 'integer', 'min:1',
            ],

            'lender_order_allowed_commodity_types' => [
                'nullable', 'array', 'required_if:allow_preferred_commodity_in_order,true',
            ],

            'lender_order_allowed_commodity_types.*' => [
                'required', new CheckActiveCommodityTypeRule,
            ],

            'allowed_financing_order_types' => [
                'required',
                'array',
                'min:1',
            ],

            'allowed_financing_order_types.*' => [
                'required',
                'integer',
                new EnumValue(FinancingOrderTypeEnum::class, false),
            ],
        ];
    }

    public function attributes()
    {
        return [
            'lender_order_allowed_commodity_types' => 'Allowed order commodity types',
            'allow_preferred_commodity_in_order' => 'Allow Commodity Type by Order',
        ];
    }

    public function messages()
    {
        return [
            'lender_order_allowed_commodity_types.required_if' => 'The :attribute field is required when :other field is On.',
            'allowed_financing_order_types.required' => 'The field is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $field = 'lender_order_allowed_commodity_types';
            $errors = $validator->errors();
            $hasItemError = false;
            $items = $this->input($field, []);

            foreach ($items as $idx => $val) {
                if ($errors->has("$field.$idx")) {
                    $hasItemError = true;
                    break;
                }
            }

            if ($hasItemError && ! $errors->has($field)) {
                $validator->errors()->add(
                    $field,
                    'The selected Allowed order commodity types is invalid or inactive.'
                );
            }
        });
    }
}
