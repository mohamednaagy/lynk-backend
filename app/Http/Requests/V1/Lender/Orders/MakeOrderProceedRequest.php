<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Models\TraderOrder;
use App\Rules\CheckAllowedFinancingOrderProceedCaseRule;
use Illuminate\Foundation\Http\FormRequest;

class MakeOrderProceedRequest extends FormRequest
{
    private TraderOrder $traderOrder;

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
        $this->traderOrder = $this->order->activeTraderOrder()->firstOrFail();

        return [
            'case' => ['required', 'string', new CheckAllowedFinancingOrderProceedCaseRule($this->traderOrder)],
            'client_wakala' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }
}
