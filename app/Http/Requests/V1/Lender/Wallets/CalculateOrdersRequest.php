<?php

namespace App\Http\Requests\V1\Lender\Wallets;

use App\Rules\MoneyValueRule;
use Illuminate\Foundation\Http\FormRequest;

class CalculateOrdersRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'gt:0', new MoneyValueRule],
        ];
    }
}
