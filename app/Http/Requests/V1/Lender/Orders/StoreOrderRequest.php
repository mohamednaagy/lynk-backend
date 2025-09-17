<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\FinancingOrderStatus;
use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Http\Requests\V1\Lender\Orders\Validators\AbstractFinancingOrderTypeValidator;
use App\Http\Requests\V1\Lender\Orders\Validators\FinancingOrderTypeValidatorFactory;
use App\Models\Company;
use App\Models\Lender;
use App\Rules\ValidCommodityTypeAtFinancingOrderRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;

    protected $stopOnFirstFailure = true;

    private AbstractFinancingOrderTypeValidator $financingOrderValidator;

    private Lender $lender;

    private int $type;


    /**
     * Always authorize this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return array_merge(
            $this->baseRules(),
            $this->financingOrderValidator->getRules()
        );
    }

    /**
     * Validation messages.
     */
    public function messages(): array
    {
        return $this->financingOrderValidator->getMessages();
    }

    /**
     * Validation attributes.
     */
    public function attributes(): array
    {
        return $this->financingOrderValidator->getAttributes();
    }

    /**
     * Prepare input and initialize validator.
     */
    protected function prepareForValidation(): void
    {
        $this->getLender();
        $this->setFinancingOrderType();

        $this->merge(['type' => $this->type]);

        $this->initFinancingOrderValidator();
    }

    /**
     * Base rules applied before delegating to financing order validator.
     */
    protected function baseRules(): array
    {
        return [
            'commodity_type_id' => [
                'nullable',
                'numeric',
                new ValidCommodityTypeAtFinancingOrderRule($this->lender->id),
            ],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
                $this->handleUniqueReferenceNumber(),
            ],
        ];
    }


    /**
     * Unique reference number rule if lender requires it.
     */
    protected function handleUniqueReferenceNumber(): ?Unique
    {
        if ($this->lender->isForceUniqueReferenceNumber()) {
            return $this->lender
                ->unique('financing_orders', 'reference_number')
                ->whereNot('status', FinancingOrderStatus::Cancelled);
        }

        return null;
    }

    /**
     * Resolve lender instance.
     */
    private function getLender(): void
    {
        $this->lender = tenant()->lender;
    }

    /**
     * Resolve financing order type from request or lender defaults.
     */
    private function setFinancingOrderType(): void
    {
        $this->type = $this->input('type',$this->lender->default_financing_order_type);
    }

    /**
     * Build financing order validator instance.
     */
    private function initFinancingOrderValidator(): void
    {
        $this->financingOrderValidator = FinancingOrderTypeValidatorFactory::create($this->type);
    }
}
