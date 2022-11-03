<?php

namespace App\Http\Requests\V1\Lender\Orders;

use App\Enums\FinancingOrderProceedCase;
use BenSampo\Enum\Rules\EnumValue;
use Illuminate\Foundation\Http\FormRequest;

class MakeOrderProceedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'case' => ['required', 'string', new EnumValue(FinancingOrderProceedCase::class)],
        ];
    }
}
