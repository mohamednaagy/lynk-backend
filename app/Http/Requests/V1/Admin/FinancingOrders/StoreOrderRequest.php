<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;

use App\Enums\CommodityTypeStatus;
use App\Enums\FinancingOrderStatus;
use App\Enums\FinancingProductEnum;
use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Http\Requests\V1\Admin\FinancingOrders\Validators\FinancingProductValidatorFactory;
use App\Http\Requests\V1\Admin\FinancingOrders\Validators\FinancingProductValidatorInterface;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use BenSampo\Enum\Rules\EnumValue;


class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;
    private ?FinancingProductValidatorInterface $productValidator = null;

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
    public function rules(): array
    {
        $baseRules = $this->getBaseRules();
        
        $validator = $this->getProductValidator();
        
        return $validator ? $validator->getRules($baseRules) : $baseRules;
    }

    /**
     * Get base validation rules that apply to all financing products
     */
    protected function getBaseRules(): array
    {

        return [
            'reference_number' => ['nullable', 'string', 'max:100', $this->handleUniqueReferenceNumber()],
            'commodity_type_id' => ['nullable', 'numeric', function ($attribute, $value, $fail) {
                $this->validateCommodityType($attribute, $value, $fail);
            }],
        ];
    }

    /**
     * Get the appropriate product validator
     */
    protected function getProductValidator(): ?FinancingProductValidatorInterface
    {
        $this->productValidator = FinancingProductValidatorFactory::create(
                $this->input('financing_product_id')
        );
        return $this->productValidator;
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        $validator = $this->getProductValidator();
        
        return $validator ? $validator->getMessages() : [];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        $validator = $this->getProductValidator();
        
        return $validator ? $validator->getAttributes() : [];
    }


    protected function prepareForValidation()
    {
        if (is_null($this->input('financing_product_id')) && $this->filled('company_id')) {
            $company = Company::find($this->input('company_id'));
            $defaultFinancingProductId = $company->lender?->lenderDetail?->default_financing_product_id;
            if ($company && $defaultFinancingProductId) {
                $this->merge([
                    'financing_product_id' => $defaultFinancingProductId,
                ]);
            }
        }
    }

    /**
     * Custom validation for commodity_type_id
     */
    protected function validateCommodityType(string $attribute, $value, \Closure $fail): void
    {
        if (is_null($value) || $value === '') {
            return;
        }

        /** @var Company $company */
        $company = Company::find(request('company_id'));

        $allowCommoditySelection = $company->lender?->lenderDetail?->allow_preferred_commodity_in_order ?? false;

        if (! $allowCommoditySelection) {
            $fail('Order not created. Commodity type selection is not allowed for this company.');
            return;
        }

        $commodityTypeExists = $company->lenderOrderAllowedCommodityTypes()
            ->where('commodity_types.id', $value)
            ->where('commodity_types.status', CommodityTypeStatus::Active)
            ->exists();

        if (! $commodityTypeExists) {
            $fail('Order not created. Invalid commodity type '.$value.' for this company.');
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
