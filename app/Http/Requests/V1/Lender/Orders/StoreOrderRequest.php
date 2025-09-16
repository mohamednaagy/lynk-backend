<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\CommodityTypeStatus;
use App\Enums\FinancingOrderStatus;
use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Http\Requests\V1\Lender\Orders\Validators\AbstractFinancialProductValidator;
use App\Http\Requests\V1\Lender\Orders\Validators\FinancialProductValidatorInterface;
use App\Http\Requests\V1\Lender\Orders\Validators\FinancialProductValidatorFactory;
use App\Models\Company;
use App\Models\CompanyLenderDetail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;

    private AbstractFinancialProductValidator $productValidator;

    private Company $company;

    private CompanyLenderDetail $lenderDetail;

    private int $financialProductId ;

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
        return  $this->productValidator->getRules();
    }



    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return $this->productValidator->getMessages();

    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return  $this->productValidator->getAttributes();
    }

    protected function prepareForValidation()
    {
        $this->loadCompanyAndLenderDetail();
        $this->setFinancialProductId();
        $this->merge([
            'financial_product_id' => $this->financialProductId,
        ]);
        $this->initProductValidator();
    }

    protected function loadCompanyAndLenderDetail(): void
    {
        $this->company = tenant();
        $this->lenderDetail = $this->company->lender?->lenderDetail;
    }

    private function setFinancialProductId(): void
    {
        $this->financialProductId = $this->input('financial_product_id') ?? $this->lenderDetail->default_financial_product_id;
    }



    protected function initProductValidator(): AbstractFinancialProductValidator
    {          
        $this->productValidator = FinancialProductValidatorFactory::create($this->financialProductId , $this->company->id);
        return $this->productValidator;
    }
}
