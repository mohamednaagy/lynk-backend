<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;


use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Http\Requests\V1\Admin\FinancingOrders\Validators\AbstractOrderTypeValidator;
use App\Http\Requests\V1\Admin\FinancingOrders\Validators\OrderTypeValidatorFactory;
use App\Models\Lender;
use Illuminate\Foundation\Http\FormRequest;
use Request;

class StoreOrderRequest extends FormRequest
{
    use RequestHasMobileVerification;

    private AbstractOrderTypeValidator $productValidator;

    private Lender $lender;

    private int $type ;

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
        return  $this->productValidator->getRules() ;
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return  $this->productValidator->getMessages();
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return $this->productValidator->getAttributes() ;
    }


    protected function prepareForValidation()
    {
        $this->getLender();
        $this->settype();
        $this->merge([
            'type' => $this->type,
        ]);
        $this->initProductValidator();
    }

    
    protected function getLender(): void
    {
        $this->lender = Lender::find($this->input('company_id'));
    }

    private function settype(): void
    {
        $this->type = $this->input('type' , $this->lender->default_type); 
    }


    protected function initProductValidator(): AbstractOrderTypeValidator
    {          
        $this->productValidator = OrderTypeValidatorFactory::create($this->type , $this->lender->id);
        return $this->productValidator;
    }



}
