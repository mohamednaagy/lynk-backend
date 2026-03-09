<?php

namespace App\Http\Requests\V1\Admin\Companies;

use App\Enums\CompanyMarketType;
use App\Enums\TraderOrderMode;
use App\Rules\CheckActiveCommodityTypeRule;
use App\Rules\CompanyUniqueNameRule;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartialUpdateCompanyRequest extends FormRequest
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
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:255'],
            'unique_name' => [
                'sometimes',
                'string',
                'min:3',
                'max:255',
                new CompanyUniqueNameRule,
                Rule::unique('companies', 'unique_name')->where('type', $this->route('lender')->type ?? '')
                    ->ignore($this->route('lender')),
            ],
            'company_cr' => [
                'sometimes',
                'string',
                'size:10',
                Rule::unique('company_lender_details', 'company_cr')
                    ->ignore($this->route('lender')?->id, 'company_id'),
            ],
            'contract_number' => [
                'sometimes',
                'string',
                Rule::unique('company_lender_details', 'contract_number')
                    ->ignore($this->route('lender')?->id, 'company_id'),
                'min:4',
                'max:16',
            ],
            'does_order_require_approval' => [
                'sometimes',
                'boolean',
            ],
            'auto_complete_murabaha_order' => [
                'sometimes',
                'boolean',
            ],
            'require_initiate_trade_request' => [
                'sometimes',
                'required_if:trading_mode,'.TraderOrderMode::Automatic,
                'boolean',
            ],
            'public_status_comment' => [
                'nullable',
                'sometimes',
                'string',
                'max:1000',
            ],
            'internal_status_comment' => [
                'nullable',
                'sometimes',
                'string',
                'max:1000',
            ],
            'notifications_email' => [
                'sometimes',
                'email:filter',
                'string',
                'max:255',
            ],
            'allow_preferred_commodity_in_order' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
            'force_unique_reference_number' => [
                'sometimes',
                'boolean',
            ],
            'trading_mode' => [
                'sometimes',
                'string',
                new EnumValue(TraderOrderMode::class, false),
            ],
            'preferred_market_type' => [
                'sometimes',
                'nullable',
                'required_if:trading_mode,'.TraderOrderMode::Automatic,
                'integer',
                new EnumValue(CompanyMarketType::class, false),
            ],
            'preferred_commodity_types' => [
                'sometimes', 'nullable', 'array',
            ],
            'preferred_commodity_types.*' => [
                'sometimes', 'required', 'exists:commodity_types,id', new CheckActiveCommodityTypeRule,
            ],
            'default_contract_sign_time_limit' => [
                'sometimes', 'nullable', 'integer', 'min:1',
            ],
            'lender_order_allowed_commodity_types' => [
                'sometimes', 'nullable', 'array',
            ],
            'lender_order_allowed_commodity_types.*' => [
                'sometimes', 'required', new CheckActiveCommodityTypeRule,
            ],
            'allowed_financing_order_types' => [
                'sometimes', 'array',
            ],
            'allowed_financing_order_types.*' => [
                'sometimes', 'integer', new EnumValue(\App\Enums\FinancingOrderTypeEnum::class, false),
            ],
            'min_wallet_limit' => ['sometimes', 'nullable', 'numeric'],
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
            'lender_order_allowed_commodity_types.required_if' => 'The :attribute field is required when :other field is :value.',
            'allowed_financing_order_types.required' => 'The field is required.',
        ];
    }
}
