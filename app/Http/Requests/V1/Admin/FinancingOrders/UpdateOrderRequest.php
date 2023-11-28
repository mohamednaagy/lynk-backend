<?php

namespace App\Http\Requests\V1\Admin\FinancingOrders;

use App\Enums\FinancingOrderStatus;
use App\Http\Requests\Traits\RequestHasMobileVerification;
use App\Models\Company;
use App\Models\FinancingOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Unique;

class UpdateOrderRequest extends FormRequest
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
            'reference_number' => ['nullable', 'string', 'max:100', $this->handleUniqueReferenceNumber()],
            'national_id' => ['required', 'integer', 'digits:10', 'gt:0'],
            'phone_country_code' => ['required_with:phone_number', 'string', 'size:2'],
            'phone_number' => ['required', 'phone:phone_country_code,mobile', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'selling_price' => ['required', 'numeric', 'gte:amount'],
        ];
    }

    private function handleUniqueReferenceNumber(): ?Unique
    {
        /** @var FinancingOrder $financingOrder */
        $financingOrder = $this->route('order');
        /** @var Company $company */
        $company = $financingOrder->company;
        if ($company && $company->force_unique_reference_number) {
            return $company->unique('financing_orders', 'reference_number')
                ->whereNot('status', FinancingOrderStatus::Cancelled)
                ->ignoreModel($financingOrder);
        }

        return null;
    }
}
