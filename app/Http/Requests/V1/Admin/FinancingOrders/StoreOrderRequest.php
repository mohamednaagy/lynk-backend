<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;

use App\Enums\CommodityTypeStatus;
use App\Enums\FinancingOrderStatus;
use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;

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
            'company_id' => ['required', Rule::exists(Company::class, 'id')],
            'customer_name' => ['required', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:100', $this->handleUniqueReferenceNumber()],
            'national_id' => ['required', 'integer', 'digits:10', 'gt:0'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required_if:is_verification_required,true', 'string', 'phone:phone_country_code,mobile'],
            'amount' => ['required', 'numeric', 'gte:1'],
            'selling_price' => ['required', 'numeric', 'gte:amount'],
            'is_verification_required' => ['required', 'boolean'],
            'commodity_type_id' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
                $this->validateCommodityType($attribute, $value, $fail);
            }],
        ];
    }

    /**
     * Custom validation for commodity_type_id
     *
     * @param  mixed  $value
     */
    protected function validateCommodityType(string $attribute, $value, \Closure $fail): void
    {
        // If value is null or empty, allow it (valid scenario)
        if (is_null($value) || $value === '') {
            return;
        }

        /** @var Company $company */
        $company = Company::find(request('company_id'));

        // Check if the company setting allows commodity type selection
        $allowCommoditySelection = $company->lender?->lenderDetail?->allow_preferred_commodity_in_order ?? false;

        // If field is provided (not null/empty) but setting is OFF, fail
        if (! $allowCommoditySelection) {
            $fail($attribute, 'Order not created. Commodity type selection is not allowed for this company.');
            
            return;
        }

        // If we reach here, value is provided and setting is ON, so validate the commodity type
        $commodityTypeExists = $company->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.id', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();
        
        if (! $commodityTypeExists) {
            $fail($attribute, 'Order not created. Invalid commodity type '.$value.' for this company.');
        }
    }

    private function handleUniqueReferenceNumber(): ?Unique
    {
        /** @var Company $company */
        $company = Company::find($this->input('company_id'));
        if ($company?->lender->lenderDetail->force_unique_reference_number) {
            return $company->unique('financing_orders', 'reference_number')
                ->whereNot('status', FinancingOrderStatus::Cancelled);
        }

        return null;
    }
}
