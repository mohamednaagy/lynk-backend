<?php

namespace App\Http\Requests\V1\Admin\Companies\Lenders\Orders;

use App\Rules\CheckAllowedFinancingOrderProceedCaseRule;
use App\Rules\CheckProceedOrderSequenceRule;
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
        return [
            'case' => [
                'required',
                'string',
                new CheckAllowedFinancingOrderProceedCaseRule($this->order),
                new CheckProceedOrderSequenceRule($this->order),
            ],
            'client_wakala' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }
}
