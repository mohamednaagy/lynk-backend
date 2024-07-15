<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\FinancingOrderProceedCase;
use App\Enums\Trader;
use App\Http\Requests\Traits\RequestHasClientWakala;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class MakeOrderProceedRequest extends FormRequest
{
    use RequestHasClientWakala;

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
            'case' => ['required', 'string', $this->checkFinancingOrderProceedCases()],
            'client_wakala' => ['nullable', 'file', 'mimes:pdf,png,jpeg,jpg'],
        ];
    }

    /**
     * @return string|\BenSampo\Enum\Rules\EnumValue
     */
    public function checkFinancingOrderProceedCases()
    {
        $traderOrder = $this->order->activeTraderOrder()->firstOrFail();
        if ($traderOrder->isProvider(Trader::Lynk)) {
            return 'in:'.FinancingOrderProceedCase::ContractAndClientWakalaCompleted;
        }

        return new EnumValue(FinancingOrderProceedCase::class);
    }

    public function messages(): array
    {
        return [
            'case.in' => __('validation.attributes.invalid_case_proceed'),
        ];
    }
}
