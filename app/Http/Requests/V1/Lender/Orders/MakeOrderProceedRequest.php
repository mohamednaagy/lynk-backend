<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Exceptions\OrderHasNoActiveTradeRequestException;
use App\Rules\CheckAllowedFinancingOrderProceedCaseRule;
use Illuminate\Foundation\Http\FormRequest;

class MakeOrderProceedRequest extends FormRequest
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
        $traderOrder = $this->order->activeTraderOrder()->first();

        if (! $traderOrder) {
            throw new OrderHasNoActiveTradeRequestException;
        }

        return [
            'case' => ['required', 'string', new CheckAllowedFinancingOrderProceedCaseRule($traderOrder)],
            'client_wakala' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }
}
